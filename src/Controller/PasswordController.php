<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Service\PasswordResetService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/password')]
class PasswordController extends AbstractController
{
    #[Route('/forgot', name: 'app_password_forgot', methods: ['GET', 'POST'])]
    public function forgot(
        Request $request,
        PasswordResetService $passwordResetService,
    ): Response {
        if ($request->isMethod('POST')) {
            $email = $request->request->get('email', '');
            $ip = $request->getClientIp() ?? '0.0.0.0';

            // Always return success to avoid email enumeration
            $passwordResetService->requestReset($email, $ip);

            $this->addFlash('success', 'Si cet email existe, vous recevrez un lien de réinitialisation dans quelques minutes.');
            return $this->redirectToRoute('app_password_forgot');
        }

        return $this->render('security/password_forgot.html.twig');
    }

    #[Route('/reset/{token}', name: 'app_password_reset_confirm', methods: ['GET', 'POST'])]
    public function reset(
        string $token,
        Request $request,
        PasswordResetService $passwordResetService,
        ValidatorInterface $validator,
    ): Response {
        // Validate token
        $tokenEntity = $passwordResetService->validateToken($token);
        if (!$tokenEntity) {
            $this->addFlash('error', 'Ce lien est invalide ou expiré. Veuillez faire une nouvelle demande.');
            return $this->redirectToRoute('app_password_forgot');
        }

        if ($request->isMethod('POST')) {
            $newPassword = $request->request->get('password', '');
            $confirmPassword = $request->request->get('password_confirm', '');

            // Strong password validation
            $violations = $validator->validate($newPassword, [
                new Assert\NotBlank(),
                new Assert\Length(min: 10),
                new Assert\Regex(
                    pattern: '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]/',
                    message: 'Le mot de passe doit contenir au moins une majuscule, une minuscule, un chiffre et un caractère spécial.'
                ),
            ]);

            if (count($violations) > 0) {
                $this->addFlash('error', (string) $violations->get(0)->getMessage());
                return $this->render('security/password_reset.html.twig', ['token' => $token]);
            }

            if ($newPassword !== $confirmPassword) {
                $this->addFlash('error', 'Les mots de passe ne correspondent pas.');
                return $this->render('security/password_reset.html.twig', ['token' => $token]);
            }

            $success = $passwordResetService->confirmReset($token, $newPassword);

            if ($success) {
                $this->addFlash('success', 'Mot de passe mis à jour avec succès. Vous pouvez maintenant vous connecter.');
                return $this->redirectToRoute('app_login');
            }

            $this->addFlash('error', 'Une erreur est survenue. Veuillez réessayer.');
        }

        return $this->render('security/password_reset.html.twig', ['token' => $token]);
    }

    #[Route('/change', name: 'app_password_change', methods: ['GET', 'POST'])]
    public function change(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        ValidatorInterface $validator,
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_USER');

        /** @var User $user */
        $user = $this->getUser();

        if ($request->isMethod('POST')) {
            $currentPassword = $request->request->get('current_password', '');
            $newPassword = $request->request->get('password', '');
            $confirmPassword = $request->request->get('password_confirm', '');

            if (!$passwordHasher->isPasswordValid($user, $currentPassword)) {
                $this->addFlash('error', 'Mot de passe actuel incorrect.');
                return $this->render('dashboard/security/change_password.html.twig');
            }

            $violations = $validator->validate($newPassword, [
                new Assert\NotBlank(),
                new Assert\Length(min: 10),
                new Assert\Regex(
                    pattern: '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]/',
                    message: 'Le mot de passe doit contenir au moins une majuscule, une minuscule, un chiffre et un caractère spécial.'
                ),
            ]);

            if (count($violations) > 0) {
                $this->addFlash('error', (string) $violations->get(0)->getMessage());
                return $this->render('dashboard/security/change_password.html.twig');
            }

            if ($newPassword !== $confirmPassword) {
                $this->addFlash('error', 'Les mots de passe ne correspondent pas.');
                return $this->render('dashboard/security/change_password.html.twig');
            }

            $hashedPassword = $passwordHasher->hashPassword($user, $newPassword);
            $user->setPassword($hashedPassword);
            $user->setMustChangePassword(false);

            $em = $this->container->get('doctrine.orm.entity_manager');
            $em->flush();

            $this->addFlash('success', 'Mot de passe mis à jour avec succès.');
            return $this->redirectToRoute('app_dashboard');
        }

        return $this->render('dashboard/security/change_password.html.twig');
    }
}
