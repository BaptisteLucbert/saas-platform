<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\AuditLog;
use App\Entity\PasswordResetToken;
use App\Entity\User;
use App\Repository\PasswordResetTokenRepository;
use App\Repository\UserRepository;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Environment;

class PasswordResetService
{
    private const TOKEN_BYTES = 32;
    private const MAX_ATTEMPTS_PER_IP = 5;
    private const WINDOW_MINUTES = 60;

    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly PasswordResetTokenRepository $tokenRepository,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly MailerInterface $mailer,
        private readonly Environment $twig,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly AuditLogService $auditLogService,
        private readonly string $mailerFrom,
    ) {
    }

    /**
     * Request a password reset - silently does nothing if email not found (security)
     */
    public function requestReset(string $email, string $ipAddress): bool
    {
        $user = $this->userRepository->findByEmail($email);
        if (!$user) {
            // Silent failure - don't reveal if email exists
            return true;
        }

        // Invalidate old tokens
        $this->tokenRepository->invalidateAllForUser($user);

        // Generate secure token
        $plainToken = bin2hex(random_bytes(self::TOKEN_BYTES));
        $tokenHash = hash('sha256', $plainToken);

        $token = new PasswordResetToken();
        $token->setUser($user);
        $token->setTokenHash($tokenHash);
        $token->setRequestIp($ipAddress);

        $this->tokenRepository->save($token, true);

        // Generate reset URL
        $resetUrl = $this->urlGenerator->generate(
            'app_password_reset_confirm',
            ['token' => $plainToken],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        // Send email
        $html = $this->twig->render('email/password_reset.html.twig', [
            'user' => $user,
            'resetUrl' => $resetUrl,
            'expiresInMinutes' => 15,
        ]);

        $emailMessage = (new Email())
            ->from($this->mailerFrom)
            ->to($user->getEmail())
            ->subject('Réinitialisation de votre mot de passe')
            ->html($html);

        $this->mailer->send($emailMessage);

        $this->auditLogService->log(
            AuditLog::ACTION_PASSWORD_RESET_REQUEST,
            $user,
            ['ip' => $ipAddress],
            'info'
        );

        return true;
    }

    public function confirmReset(string $plainToken, string $newPassword): bool
    {
        $tokenHash = hash('sha256', $plainToken);
        $token = $this->tokenRepository->findValidByTokenHash($tokenHash);

        if (!$token || !$token->isValid()) {
            return false;
        }

        $user = $token->getUser();
        if (!$user) {
            return false;
        }

        // Hash new password
        $hashedPassword = $this->passwordHasher->hashPassword($user, $newPassword);
        $user->setPassword($hashedPassword);
        $user->setMustChangePassword(false);

        // Invalidate token
        $token->setIsUsed(true);

        $this->userRepository->save($user, true);

        $this->auditLogService->log(
            AuditLog::ACTION_PASSWORD_RESET,
            $user,
            [],
            'info'
        );

        return true;
    }

    public function validateToken(string $plainToken): ?PasswordResetToken
    {
        $tokenHash = hash('sha256', $plainToken);
        return $this->tokenRepository->findValidByTokenHash($tokenHash);
    }
}
