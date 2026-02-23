<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\User;
use App\Repository\PaymentRepository;
use App\Repository\ServiceRepository;
use App\Repository\SubscriptionRepository;
use App\Repository\UserRepository;
use App\Service\AuditLogService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin')]
#[IsGranted('ROLE_ADMIN')]
class AdminController extends AbstractController
{
    #[Route('', name: 'app_admin_dashboard', methods: ['GET'])]
    public function dashboard(
        UserRepository $userRepository,
        SubscriptionRepository $subscriptionRepository,
        PaymentRepository $paymentRepository,
    ): Response {
        $stats = [
            'total_users' => count($userRepository->findAll()),
            'active_subscriptions' => $subscriptionRepository->countActive(),
            'total_revenue' => $paymentRepository->getTotalRevenue(),
            'recent_users' => $userRepository->findRecentlyRegistered(7),
        ];

        return $this->render('admin/dashboard.html.twig', [
            'stats' => $stats,
        ]);
    }

    #[Route('/users', name: 'app_admin_users', methods: ['GET'])]
    public function users(UserRepository $userRepository, Request $request): Response
    {
        $search = $request->query->get('q', '');
        $users = $userRepository->findAll();

        return $this->render('admin/users/index.html.twig', [
            'users' => $users,
            'search' => $search,
        ]);
    }

    #[Route('/users/{id}', name: 'app_admin_user_show', methods: ['GET'])]
    public function userShow(User $user): Response
    {
        return $this->render('admin/users/show.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/users/{id}/suspend', name: 'app_admin_user_suspend', methods: ['POST'])]
    public function userSuspend(
        User $user,
        EntityManagerInterface $em,
        AuditLogService $auditLogService,
        Request $request,
    ): Response {
        if (!$this->isCsrfTokenValid('suspend_user_' . $user->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');
            return $this->redirectToRoute('app_admin_user_show', ['id' => $user->getId()]);
        }

        $user->setIsSuspended(!$user->isSuspended());
        $em->flush();

        $action = $user->isSuspended() ? 'Compte suspendu' : 'Compte réactivé';
        /** @var User|null $admin */
        $admin = $this->getUser();
        $auditLogService->log(
            $user->isSuspended() ? 'admin_suspend_user' : 'admin_reactivate_user',
            $user,
            ['admin' => $admin?->getEmail()],
            'warning'
        );

        $this->addFlash('success', $action . ' avec succès.');
        return $this->redirectToRoute('app_admin_user_show', ['id' => $user->getId()]);
    }

    #[Route('/users/{id}/force-reset', name: 'app_admin_force_reset', methods: ['POST'])]
    public function forcePasswordReset(
        User $user,
        EntityManagerInterface $em,
        AuditLogService $auditLogService,
        Request $request,
    ): Response {
        if (!$this->isCsrfTokenValid('force_reset_' . $user->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');
            return $this->redirectToRoute('app_admin_user_show', ['id' => $user->getId()]);
        }

        $user->setMustChangePassword(true);
        $em->flush();

        /** @var User|null $admin */
        $admin = $this->getUser();
        $auditLogService->log('admin_force_password_reset', $user, ['admin' => $admin?->getEmail()], 'warning');

        $this->addFlash('success', 'Réinitialisation de mot de passe forcée.');
        return $this->redirectToRoute('app_admin_user_show', ['id' => $user->getId()]);
    }

    #[Route('/subscriptions', name: 'app_admin_subscriptions', methods: ['GET'])]
    public function subscriptions(SubscriptionRepository $subscriptionRepository): Response
    {
        $subscriptions = $subscriptionRepository->findAll();

        return $this->render('admin/subscriptions.html.twig', [
            'subscriptions' => $subscriptions,
        ]);
    }

    #[Route('/payments', name: 'app_admin_payments', methods: ['GET'])]
    public function payments(PaymentRepository $paymentRepository): Response
    {
        $payments = $paymentRepository->findAll();

        return $this->render('admin/payments.html.twig', [
            'payments' => $payments,
        ]);
    }

    #[Route('/services', name: 'app_admin_services', methods: ['GET'])]
    public function services(ServiceRepository $serviceRepository): Response
    {
        return $this->render('admin/services.html.twig', [
            'services' => $serviceRepository->findAll(),
        ]);
    }

    #[Route('/services/{id}/toggle', name: 'app_admin_service_toggle', methods: ['POST'])]
    public function toggleService(
        \App\Entity\Service $service,
        EntityManagerInterface $em,
        Request $request,
    ): Response {
        if (!$this->isCsrfTokenValid('toggle_service_' . $service->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');
            return $this->redirectToRoute('app_admin_services');
        }

        $service->setIsActive(!$service->isActive());
        $em->flush();

        $this->addFlash('success', 'Service ' . ($service->isActive() ? 'activé' : 'désactivé') . '.');
        return $this->redirectToRoute('app_admin_services');
    }

    #[Route('/services/{id}/edit', name: 'app_admin_service_edit', methods: ['GET', 'POST'])]
    public function serviceEdit(
        \App\Entity\Service $service,
        EntityManagerInterface $em,
        Request $request,
    ): Response {
        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('edit_service_' . $service->getId(), $request->request->get('_token'))) {
                $this->addFlash('error', 'Token CSRF invalide.');
            } else {
                $service->setName((string) $request->request->get('name'));
                $service->setMonthlyPrice((string) $request->request->get('monthlyPrice'));
                $service->setAnnualPrice((string) $request->request->get('annualPrice'));
                $service->setWhopMonthlyPlanId($request->request->get('whopMonthlyPlanId'));
                $service->setWhopMonthlyPlanIdSandbox($request->request->get('whopMonthlyPlanIdSandbox'));
                $service->setWhopAnnualPlanId($request->request->get('whopAnnualPlanId'));
                $service->setWhopAnnualPlanIdSandbox($request->request->get('whopAnnualPlanIdSandbox'));

                $em->flush();
                $this->addFlash('success', 'Service mis à jour avec succès.');
                return $this->redirectToRoute('app_admin_services');
            }
        }

        return $this->render('admin/services/edit.html.twig', [
            'service' => $service,
        ]);
    }

    #[Route('/logs', name: 'app_admin_logs', methods: ['GET'])]
    public function logs(\App\Repository\AuditLogRepository $auditLogRepository): Response
    {
        return $this->render('admin/logs.html.twig', [
            'logs' => $auditLogRepository->findBy([], ['createdAt' => 'DESC'], 100),
        ]);
    }
}
