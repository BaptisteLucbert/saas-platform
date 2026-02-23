<?php

declare(strict_types=1);

namespace App\Security;

use App\Entity\AuditLog;
use App\Entity\LoginAttempt;
use App\Entity\User;
use App\Repository\LoginAttemptRepository;
use App\Service\AuditLogService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;

class LoginSuccessHandler implements AuthenticationSuccessHandlerInterface
{
    public function __construct(
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly EntityManagerInterface $entityManager,
        private readonly AuditLogService $auditLogService,
        private readonly LoginAttemptRepository $loginAttemptRepository
    ) {
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token): Response
    {
        $user = $token->getUser();
        if (!$user instanceof User) {
            return new RedirectResponse($this->urlGenerator->generate('app_home'));
        }

        // Log success
        $attempt = new LoginAttempt();
        $attempt->setUser($user);
        $attempt->setEmail($user->getEmail());
        $attempt->setIpAddress($request->getClientIp() ?? '0.0.0.0');
        $attempt->setUserAgent($request->headers->get('User-Agent'));
        $attempt->setSuccess(true);

        $this->entityManager->persist($attempt);

        // Update user last login
        $user->setLastLoginAt(new \DateTimeImmutable());
        $user->setLastLoginIp($request->getClientIp());

        $this->entityManager->flush();

        $this->auditLogService->log(AuditLog::ACTION_LOGIN, $user, [], 'info', $request);

        return new RedirectResponse($this->urlGenerator->generate('app_dashboard'));
    }
}
