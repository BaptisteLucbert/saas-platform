<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\AuditLog;
use App\Entity\User;
use App\Repository\AuditLogRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class AuditLogService
{
    public function __construct(
        private readonly AuditLogRepository $auditLogRepository,
        private readonly RequestStack $requestStack,
    ) {
    }

    public function log(
        string $action,
        ?User $user = null,
        array $context = [],
        string $severity = 'info',
        ?Request $request = null
    ): void {
        $request = $request ?? $this->requestStack->getCurrentRequest();

        $log = new AuditLog();
        $log->setUser($user);
        $log->setAction($action);
        $log->setSeverity($severity);
        $log->setContext($context ?: null);

        if ($request) {
            $log->setIpAddress($request->getClientIp());
            $log->setUserAgent($request->headers->get('User-Agent'));
        }

        $this->auditLogRepository->save($log, true);
    }

    public function logLogin(User $user, bool $success, ?Request $request = null): void
    {
        $this->log(
            $success ? AuditLog::ACTION_LOGIN : 'login_failed',
            $user,
            ['email' => $user->getEmail(), 'success' => $success],
            $success ? 'info' : 'warning',
            $request
        );
    }

    public function logPasswordReset(User $user, ?Request $request = null): void
    {
        $this->log(AuditLog::ACTION_PASSWORD_RESET, $user, [], 'info', $request);
    }

    public function logPayment(User $user, string $action, array $context = []): void
    {
        $this->log($action, $user, $context, 'info');
    }

    public function logSecurity(User $user, string $action, array $context = []): void
    {
        $this->log($action, $user, $context, 'warning');
    }
}
