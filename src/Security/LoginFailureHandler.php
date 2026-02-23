<?php

declare(strict_types=1);

namespace App\Security;

use App\Entity\AuditLog;
use App\Entity\LoginAttempt;
use App\Repository\UserRepository;
use App\Service\AuditLogService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authentication\AuthenticationFailureHandlerInterface;
use Symfony\Component\Security\Http\SecurityRequestAttributes;

class LoginFailureHandler implements AuthenticationFailureHandlerInterface
{
    public function __construct(
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly EntityManagerInterface $entityManager,
        private readonly AuditLogService $auditLogService,
        private readonly UserRepository $userRepository
    ) {
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): Response
    {
        $email = $request->request->get('email', '');
        $ip = $request->getClientIp() ?? '0.0.0.0';

        // Log failed attempt
        $attempt = new LoginAttempt();
        $attempt->setEmail($email);
        $attempt->setIpAddress($ip);
        $attempt->setUserAgent($request->headers->get('User-Agent'));
        $attempt->setSuccess(false);

        $user = $this->userRepository->findOneBy(['email' => $email]);
        if ($user) {
            $attempt->setUser($user);
        }

        $this->entityManager->persist($attempt);
        $this->entityManager->flush();

        $this->auditLogService->log('login_failed', $user, ['email' => $email, 'reason' => $exception->getMessageKey()], 'warning', $request);

        $request->getSession()->set(SecurityRequestAttributes::AUTHENTICATION_ERROR, $exception);

        return new RedirectResponse($this->urlGenerator->generate('app_login'));
    }
}
