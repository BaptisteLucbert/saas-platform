<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\SubscriptionRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SubscriptionRepository::class)]
#[ORM\Table(name: 'subscriptions')]
#[ORM\HasLifecycleCallbacks]
class Subscription
{
    public const STATUS_ACTIVE = 'active';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_PAST_DUE = 'past_due';
    public const STATUS_TRIALING = 'trialing';
    public const STATUS_PAUSED = 'paused';
    public const INTERVAL_MONTHLY = 'monthly';
    public const INTERVAL_ANNUAL = 'annual';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'subscriptions')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $user = null;

    #[ORM\ManyToOne(targetEntity: Service::class, inversedBy: 'subscriptions')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Service $service = null;

    #[ORM\Column(type: 'string', length: 50)]
    private string $status = self::STATUS_ACTIVE;

    #[ORM\Column(type: 'string', length: 20)]
    private string $interval = self::INTERVAL_MONTHLY;

    #[ORM\Column(type: 'string', length: 150, nullable: true)]
    private ?string $whopMembershipId = null;

    #[ORM\Column(type: 'string', length: 150, nullable: true)]
    private ?string $whopUserId = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    private string $amount = '0.00';

    #[ORM\Column(type: 'string', length: 3, options: ['default' => 'EUR'])]
    private string $currency = 'EUR';

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    private ?string $couponCode = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $currentPeriodStart = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $currentPeriodEnd = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $cancelledAt = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $trialEndsAt = null;

    #[ORM\Column(type: 'boolean', options: ['default' => true])]
    private bool $cancelAtPeriodEnd = false;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function isActive(): bool
    {
        return in_array($this->status, [self::STATUS_ACTIVE, self::STATUS_TRIALING], true);
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
    public function getService(): ?Service
    {
        return $this->service;
    }
    public function setService(?Service $service): static
    {
        $this->service = $service;
        return $this;
    }
    public function getStatus(): string
    {
        return $this->status;
    }
    public function setStatus(string $status): static
    {
        $this->status = $status;
        return $this;
    }
    public function getInterval(): string
    {
        return $this->interval;
    }
    public function setInterval(string $interval): static
    {
        $this->interval = $interval;
        return $this;
    }
    public function getWhopMembershipId(): ?string
    {
        return $this->whopMembershipId;
    }
    public function setWhopMembershipId(?string $id): static
    {
        $this->whopMembershipId = $id;
        return $this;
    }
    public function getWhopUserId(): ?string
    {
        return $this->whopUserId;
    }
    public function setWhopUserId(?string $id): static
    {
        $this->whopUserId = $id;
        return $this;
    }
    public function getAmount(): string
    {
        return $this->amount;
    }
    public function setAmount(string $amount): static
    {
        $this->amount = $amount;
        return $this;
    }
    public function getCurrency(): string
    {
        return $this->currency;
    }
    public function setCurrency(string $currency): static
    {
        $this->currency = $currency;
        return $this;
    }
    public function getCouponCode(): ?string
    {
        return $this->couponCode;
    }
    public function setCouponCode(?string $couponCode): static
    {
        $this->couponCode = $couponCode;
        return $this;
    }
    public function getCurrentPeriodStart(): ?\DateTimeImmutable
    {
        return $this->currentPeriodStart;
    }
    public function setCurrentPeriodStart(?\DateTimeImmutable $currentPeriodStart): static
    {
        $this->currentPeriodStart = $currentPeriodStart;
        return $this;
    }
    public function getCurrentPeriodEnd(): ?\DateTimeImmutable
    {
        return $this->currentPeriodEnd;
    }
    public function setCurrentPeriodEnd(?\DateTimeImmutable $currentPeriodEnd): static
    {
        $this->currentPeriodEnd = $currentPeriodEnd;
        return $this;
    }
    public function getCancelledAt(): ?\DateTimeImmutable
    {
        return $this->cancelledAt;
    }
    public function setCancelledAt(?\DateTimeImmutable $cancelledAt): static
    {
        $this->cancelledAt = $cancelledAt;
        return $this;
    }
    public function getTrialEndsAt(): ?\DateTimeImmutable
    {
        return $this->trialEndsAt;
    }
    public function setTrialEndsAt(?\DateTimeImmutable $trialEndsAt): static
    {
        $this->trialEndsAt = $trialEndsAt;
        return $this;
    }
    public function isCancelAtPeriodEnd(): bool
    {
        return $this->cancelAtPeriodEnd;
    }
    public function setCancelAtPeriodEnd(bool $cancelAtPeriodEnd): static
    {
        $this->cancelAtPeriodEnd = $cancelAtPeriodEnd;
        return $this;
    }
    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }
}
