<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\AuditLog;
use App\Entity\Payment;
use App\Entity\Service as ServiceEntity;
use App\Entity\Subscription;
use App\Entity\User;
use App\Repository\PaymentRepository;
use App\Repository\ServiceRepository;
use App\Repository\SubscriptionRepository;
use App\Repository\UserRepository;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Twig\Environment;

class WhopService
{
    public function __construct(
        #[Autowire('%env(WHOP_API_KEY_PROD)%')]
        private readonly string $apiKeyProd,
        #[Autowire('%env(WHOP_API_KEY_TEST)%')]
        private readonly string $apiKeyTest,
        #[Autowire('%env(WHOP_WEBHOOK_SECRET_PROD)%')]
        private readonly string $webhookSecretProd,
        #[Autowire('%env(WHOP_WEBHOOK_SECRET_TEST)%')]
        private readonly string $webhookSecretTest,
        #[Autowire('%env(WHOP_COMPANY_ID_PROD)%')]
        private readonly string $companyIdProd,
        #[Autowire('%env(WHOP_COMPANY_ID_TEST)%')]
        private readonly string $companyIdTest,
        #[Autowire('%env(bool:WHOP_SANDBOX)%')]
        private readonly bool $isSandbox,
        #[Autowire('%env(APP_URL)%')]
        private readonly string $appUrl,
        private readonly HttpClientInterface $httpClient,
        private readonly UserRepository $userRepository,
        private readonly ServiceRepository $serviceRepository,
        private readonly SubscriptionRepository $subscriptionRepository,
        private readonly PaymentRepository $paymentRepository,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly MailerInterface $mailer,
        private readonly Environment $twig,
        private readonly AuditLogService $auditLogService,
        #[Autowire('%env(MAILER_FROM)%')]
        private readonly string $mailerFrom,
    ) {
    }

    private function getApiKey(): string
    {
        return $this->isSandbox ? $this->apiKeyTest : $this->apiKeyProd;
    }

    private function getWebhookSecret(): string
    {
        return $this->isSandbox ? $this->webhookSecretTest : $this->webhookSecretProd;
    }

    private function getCompanyId(): string
    {
        return $this->isSandbox ? $this->companyIdTest : $this->companyIdProd;
    }

    /**
     * Crée une session de paiement Whop (Checkout Configuration)
     */
    public function createCheckoutUrl(
        ServiceEntity $service,
        string $interval,
        string $redirectUrl,
        ?string $referralCode = null
    ): string {
        if ($this->isSandbox) {
            $planId = $interval === 'annual'
                ? $service->getWhopAnnualPlanIdSandbox()
                : $service->getWhopMonthlyPlanIdSandbox();
        } else {
            $planId = $interval === 'annual'
                ? $service->getWhopAnnualPlanId()
                : $service->getWhopMonthlyPlanId();
        }

        if (!$planId) {
            throw new \RuntimeException('Aucun ID de plan Whop configuré pour ce service/intervalle.');
        }

        $response = $this->httpClient->request('POST', 'https://api.whop.com/api/v1/checkout_configurations', [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->getApiKey(),
                'Content-Type' => 'application/json',
            ],
            'json' => [
                'plan_id' => $planId,
                'redirect_url' => $redirectUrl,
                'metadata' => [
                    'service_id' => (string) $service->getId(),
                    'interval' => $interval,
                    'referral_code' => $referralCode ?? '',
                ],
            ],
        ]);

        $data = $response->toArray();

        return $data['url'] ?? throw new \RuntimeException('Erreur lors de la création de la configuration de paiement Whop.');
    }

    /**
     * Valide la signature du Webhook Whop
     */
    public function validateWebhook(Request $request): bool
    {
        $signature = $request->headers->get('X-Whop-Signature');
        if (!$signature) {
            return false;
        }

        $payload = $request->getContent();
        $expectedSignature = hash_hmac('sha256', $payload, $this->getWebhookSecret());

        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Gère les événements Webhook Whop
     */
    public function handleWebhook(array $payload): void
    {
        $event = $payload['action'] ?? $payload['type'] ?? '';

        match ($event) {
            'membership.activated', 'membership.went_active' => $this->handleMembershipActivated($payload['data']),
            'membership.deactivated', 'membership.went_inactive' => $this->handleMembershipDeactivated($payload['data']),
            'payment.succeeded' => $this->handlePaymentSucceeded($payload['data']),
            default => null,
        };
    }

    private function handleMembershipActivated(array $data): void
    {
        $email = $data['email'] ?? $data['user']['email'] ?? null;
        if (!$email)
            return;

        $planId = $data['plan_id'] ?? null;
        $membershipId = (string) ($data['id'] ?? '');
        $metadata = $data['metadata'] ?? [];

        $serviceId = (int) ($metadata['service_id'] ?? 0);
        $interval = $metadata['interval'] ?? 'monthly';

        $service = $this->serviceRepository->find($serviceId);
        if (!$service) {
            // Tentative de recherche par Plan ID
            $service = $this->serviceRepository->findOneBy(['whopMonthlyPlanId' => $planId])
                ?? $this->serviceRepository->findOneBy(['whopAnnualPlanId' => $planId])
                ?? $this->serviceRepository->findOneBy(['whopMonthlyPlanIdSandbox' => $planId])
                ?? $this->serviceRepository->findOneBy(['whopAnnualPlanIdSandbox' => $planId]);
        }

        if (!$service)
            return;

        $user = $this->userRepository->findByEmail($email);
        $isNewUser = false;

        if (!$user) {
            $isNewUser = true;
            $user = $this->createUser($email, $data['user']['username'] ?? null);
        }

        // Met à jour l'ID Whop de l'utilisateur
        if (isset($data['user_id']) && !$user->getWhopUserId()) {
            $user->setWhopUserId((string) $data['user_id']);
            $this->userRepository->save($user, true);
        }

        // Créer ou mettre à jour l'abonnement
        $subscription = $this->subscriptionRepository->findOneBy(['whopMembershipId' => $membershipId]) ?? new Subscription();
        $subscription->setUser($user);
        $subscription->setService($service);
        $subscription->setWhopMembershipId($membershipId);
        $subscription->setStatus(Subscription::STATUS_ACTIVE);
        $subscription->setInterval($interval);

        // Whop date format: ISO 8601
        if (isset($data['expires_at'])) {
            $subscription->setCurrentPeriodEnd(new \DateTimeImmutable($data['expires_at']));
        }

        $this->subscriptionRepository->save($subscription, true);

        if ($isNewUser) {
            $this->sendWelcomeEmail($user, $service);
        }

        $this->auditLogService->log(
            AuditLog::ACTION_SUBSCRIPTION_CREATED,
            $user,
            ['service' => $service->getSlug(), 'provider' => 'whop']
        );
    }

    private function handleMembershipDeactivated(array $data): void
    {
        $membershipId = (string) ($data['id'] ?? '');
        $subscription = $this->subscriptionRepository->findOneBy(['whopMembershipId' => $membershipId]);

        if ($subscription) {
            $subscription->setStatus(Subscription::STATUS_CANCELLED);
            $subscription->setCancelledAt(new \DateTimeImmutable());
            $this->subscriptionRepository->save($subscription, true);

            $this->auditLogService->log(
                AuditLog::ACTION_SUBSCRIPTION_CANCELLED,
                $subscription->getUser(),
                ['membership_id' => $membershipId, 'provider' => 'whop']
            );
        }
    }

    private function handlePaymentSucceeded(array $data): void
    {
        $membershipId = (string) ($data['membership_id'] ?? '');
        $subscription = $this->subscriptionRepository->findOneBy(['whopMembershipId' => $membershipId]);

        if (!$subscription)
            return;

        $payment = new Payment();
        $payment->setUser($subscription->getUser());
        $payment->setSubscription($subscription);
        $payment->setWhopPaymentId((string) ($data['id'] ?? ''));
        $payment->setAmount((string) (($data['amount'] ?? 0) / 100));
        $payment->setCurrency(strtoupper($data['currency'] ?? 'USD'));
        $payment->setStatus(Payment::STATUS_SUCCEEDED);
        $payment->setDescription('Renouvellement Whop Membership');

        $this->paymentRepository->save($payment, true);

        $this->auditLogService->log(
            AuditLog::ACTION_PAYMENT_SUCCESS,
            $subscription->getUser(),
            ['amount' => $payment->getAmount(), 'provider' => 'whop']
        );
    }

    private function createUser(string $email, ?string $username): User
    {
        $tempPassword = $this->generateSecurePassword();
        $user = new User();
        $user->setEmail($email);
        $user->setIsActive(true);
        $user->setMustChangePassword(true);

        if ($username) {
            $user->setFirstName($username);
        }

        $user->setPassword($this->passwordHasher->hashPassword($user, $tempPassword));
        $this->userRepository->save($user, true);

        // Send email (simulé ici, à implémenter si besoin d'un template spécifique)
        $html = $this->twig->render('email/account_created.html.twig', [
            'user' => $user,
            'tempPassword' => $tempPassword,
            'loginUrl' => $this->appUrl . '/login',
        ]);

        $emailMessage = (new Email())
            ->from($this->mailerFrom)
            ->to($email)
            ->subject('🎉 Votre accès Whop est prêt !')
            ->html($html);

        $this->mailer->send($emailMessage);

        return $user;
    }

    private function generateSecurePassword(): string
    {
        return bin2hex(random_bytes(8)) . 'A1!';
    }

    private function sendWelcomeEmail(User $user, ServiceEntity $service): void
    {
        // Géré dans createUser
    }
}
