<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Repository\PaymentRepository;
use App\Repository\ServiceRepository;
use App\Repository\SubscriptionRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use App\Service\AuditLogService;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Google\GoogleAuthenticatorInterface;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;

#[Route('/dashboard')]
class DashboardController extends AbstractController
{
    #[Route('', name: 'app_dashboard', methods: ['GET'])]
    public function index(SubscriptionRepository $subscriptionRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        /** @var User $user */
        $user = $this->getUser();

        // Redirect to change password if required
        if ($user->isMustChangePassword()) {
            return $this->redirectToRoute('app_password_change');
        }

        $subscriptions = $subscriptionRepository->findActiveByUser($user);

        return $this->render('dashboard/index.html.twig', [
            'user' => $user,
            'subscriptions' => $subscriptions,
        ]);
    }

    #[Route('/subscriptions', name: 'app_dashboard_subscriptions', methods: ['GET'])]
    public function subscriptions(SubscriptionRepository $subscriptionRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        /** @var User $user */
        $user = $this->getUser();

        $subscriptions = $subscriptionRepository->findBy(['user' => $user], ['createdAt' => 'DESC']);

        return $this->render('dashboard/subscriptions.html.twig', [
            'subscriptions' => $subscriptions,
        ]);
    }

    #[Route('/invoices', name: 'app_dashboard_invoices', methods: ['GET'])]
    public function invoices(PaymentRepository $paymentRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        /** @var User $user */
        $user = $this->getUser();

        $payments = $paymentRepository->findBy(['user' => $user], ['createdAt' => 'DESC']);

        return $this->render('dashboard/invoices.html.twig', [
            'payments' => $payments,
        ]);
    }

    #[Route('/vinted', name: 'app_dashboard_vinted', methods: ['GET'])]
    public function vinted(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        /** @var User $user */
        $user = $this->getUser();

        if (!$user->hasActiveSubscriptionForService('vinted')) {
            $this->addFlash('error', 'Vous devez être abonné au service Vinted pour accéder à cette section.');
            return $this->redirectToRoute('app_dashboard');
        }

        return $this->render('dashboard/vinted/index.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/discord', name: 'app_dashboard_discord', methods: ['GET'])]
    public function discord(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        /** @var User $user */
        $user = $this->getUser();

        return $this->render('dashboard/discord.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/settings', name: 'app_dashboard_settings', methods: ['GET'])]
    public function settings(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        return $this->render('dashboard/settings.html.twig', [
            'user' => $this->getUser(),
        ]);
    }

    #[Route('/settings/update', name: 'app_dashboard_settings_update', methods: ['POST'])]
    public function updateSettings(Request $request, EntityManagerInterface $em, AuditLogService $auditLogService): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        /** @var User $user */
        $user = $this->getUser();

        $user->setFirstName($request->request->get('firstName', ''));
        $user->setLastName($request->request->get('lastName', ''));

        $em->flush();

        $auditLogService->log('profile_updated', $user, [], 'info', $request);
        $this->addFlash('success', 'Profil mis à jour avec succès.');

        return $this->redirectToRoute('app_dashboard_settings');
    }

    #[Route('/settings/preferences', name: 'app_dashboard_settings_preferences', methods: ['POST'])]
    public function updatePreferences(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        /** @var User $user */
        $user = $this->getUser();

        $data = json_decode($request->getContent(), true);
        if (!$data) {
            return new JsonResponse(['error' => 'Données invalides'], 400);
        }

        $preferences = $user->getPreferences();
        foreach ($data as $key => $value) {
            $preferences[$key] = (bool) $value;
        }

        $user->setPreferences($preferences);
        $em->flush();

        return new JsonResponse(['success' => true]);
    }

    #[Route('/security', name: 'app_dashboard_security', methods: ['GET'])]
    public function security(GoogleAuthenticatorInterface $googleAuthenticator, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        /** @var User $user */
        $user = $this->getUser();

        $qrCodeDataUri = null;
        $secret = null;

        if (!$user->isTwoFactorEnabled()) {
            if (!$user->getTwoFactorSecret()) {
                $user->setTwoFactorSecret($googleAuthenticator->generateSecret());
                $em->flush();
            }

            $qrCodeContent = $googleAuthenticator->getQRContent($user);
            $qrCode = new QrCode(data: $qrCodeContent, size: 300, margin: 10);
            $writer = new PngWriter();
            $qrCodeDataUri = $writer->write($qrCode)->getDataUri();
            $secret = $user->getTwoFactorSecret();
        }

        return $this->render('dashboard/security/index.html.twig', [
            'user' => $user,
            'qrCodeDataUri' => $qrCodeDataUri,
            'secret' => $secret,
        ]);
    }



    #[Route('/security/2fa/enable', name: 'app_dashboard_2fa_enable', methods: ['POST'])]
    public function enable2FA(Request $request, GoogleAuthenticatorInterface $googleAuthenticator, EntityManagerInterface $em, AuditLogService $auditLogService): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        /** @var User $user */
        $user = $this->getUser();

        $code = str_replace(' ', '', (string) $request->request->get('code'));
        if ($googleAuthenticator->checkCode($user, $code)) {
            $user->setTwoFactorEnabled(true);
            $em->flush();

            $auditLogService->log('2fa_enabled', $user, [], 'warning', $request);
            $this->addFlash('success', 'L\'authentification à deux facteurs a été activée.');
            return $this->redirectToRoute('app_dashboard_security');
        }

        $this->addFlash('error', 'Code de vérification invalide.');
        return $this->redirectToRoute('app_dashboard_security', ['setup' => 1]);
    }

    #[Route('/security/2fa/disable', name: 'app_dashboard_2fa_disable', methods: ['POST'])]
    public function disable2FA(Request $request, GoogleAuthenticatorInterface $googleAuthenticator, EntityManagerInterface $em, AuditLogService $auditLogService): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        /** @var User $user */
        $user = $this->getUser();

        if (!$this->isCsrfTokenValid('disable_2fa', $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');
            return $this->redirectToRoute('app_dashboard_security');
        }

        $code = str_replace(' ', '', (string) $request->request->get('code'));
        if (!$googleAuthenticator->checkCode($user, $code)) {
            $this->addFlash('error', 'Code de vérification invalide. La 2FA n\'a pas été désactivée.');
            return $this->redirectToRoute('app_dashboard_security');
        }

        $user->setTwoFactorEnabled(false);
        $em->flush();

        $auditLogService->log('2fa_disabled', $user, [], 'danger', $request);
        $this->addFlash('success', 'L\'authentification à deux facteurs a été désactivée.');

        return $this->redirectToRoute('app_dashboard_security');
    }
}
