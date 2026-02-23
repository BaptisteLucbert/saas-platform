<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\AuditLogRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AuditLogRepository::class)]
#[ORM\Table(name: 'audit_logs')]
class AuditLog
{
    public const ACTION_LOGIN = 'login';
    public const ACTION_LOGOUT = 'logout';
    public const ACTION_PASSWORD_RESET_REQUEST = 'password_reset_request';
    public const ACTION_PASSWORD_RESET = 'password_reset';
    public const ACTION_PASSWORD_CHANGE = 'password_change';
    public const ACTION_SUBSCRIPTION_CREATED = 'subscription_created';
    public const ACTION_SUBSCRIPTION_CANCELLED = 'subscription_cancelled';
    public const ACTION_PAYMENT_SUCCESS = 'payment_success';
    public const ACTION_PAYMENT_FAILED = 'payment_failed';
    public const ACTION_ACCOUNT_CREATED = 'account_created';
    public const ACTION_ACCOUNT_SUSPENDED = 'account_suspended';
    public const ACTION_2FA_ENABLED = '2fa_enabled';
    public const ACTION_2FA_DISABLED = '2fa_disabled';
    public const ACTION_EMAIL_CHANGED = 'email_changed';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'auditLogs')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $user = null;

    #[ORM\Column(type: 'string', length: 100)]
    private string $action = '';

    #[ORM\Column(type: 'string', length: 45, nullable: true)]
    private ?string $ipAddress = null;

    #[ORM\Column(type: 'string', length: 500, nullable: true)]
    private ?string $userAgent = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $context = null;

    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    private ?string $severity = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }
    public function getUser(): ?User
    {
        return $this->user;
    }
    public function setUser(?User $user): static
    {
        $this->user = $user;
        return $this;
    }
    public function getAction(): string
    {
        return $this->action;
    }
    public function setAction(string $action): static
    {
        $this->action = $action;
        return $this;
    }
    public function getIpAddress(): ?string
    {
        return $this->ipAddress;
    }
    public function setIpAddress(?string $ipAddress): static
    {
        $this->ipAddress = $ipAddress;
        return $this;
    }
    public function getUserAgent(): ?string
    {
        return $this->userAgent;
    }
    public function setUserAgent(?string $userAgent): static
    {
        $this->userAgent = $userAgent;
        return $this;
    }
    public function getContext(): ?array
    {
        return $this->context;
    }
    public function setContext(?array $context): static
    {
        $this->context = $context;
        return $this;
    }
    public function getSeverity(): ?string
    {
        return $this->severity;
    }
    public function setSeverity(?string $severity): static
    {
        $this->severity = $severity;
        return $this;
    }
    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
