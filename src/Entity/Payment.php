<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\PaymentRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PaymentRepository::class)]
#[ORM\Table(name: 'payments')]
class Payment
{
    public const STATUS_SUCCEEDED = 'succeeded';
    public const STATUS_FAILED = 'failed';
    public const STATUS_PENDING = 'pending';
    public const STATUS_REFUNDED = 'refunded';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $user = null;

    #[ORM\ManyToOne(targetEntity: Subscription::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?Subscription $subscription = null;

    #[ORM\Column(type: 'string', length: 150, nullable: true)]
    private ?string $whopPaymentId = null;

    #[ORM\Column(type: 'string', length: 150, nullable: true)]
    private ?string $whopCheckoutId = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    private string $amount = '0.00';

    #[ORM\Column(type: 'string', length: 3, options: ['default' => 'EUR'])]
    private string $currency = 'EUR';

    #[ORM\Column(type: 'string', length: 50)]
    private string $status = self::STATUS_PENDING;

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $metadata = null;

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
    public function getSubscription(): ?Subscription
    {
        return $this->subscription;
    }
    public function setSubscription(?Subscription $subscription): static
    {
        $this->subscription = $subscription;
        return $this;
    }
    public function getWhopPaymentId(): ?string
    {
        return $this->whopPaymentId;
    }
    public function setWhopPaymentId(?string $id): static
    {
        $this->whopPaymentId = $id;
        return $this;
    }
    public function getWhopCheckoutId(): ?string
    {
        return $this->whopCheckoutId;
    }
    public function setWhopCheckoutId(?string $id): static
    {
        $this->whopCheckoutId = $id;
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
    public function getStatus(): string
    {
        return $this->status;
    }
    public function setStatus(string $status): static
    {
        $this->status = $status;
        return $this;
    }
    public function getDescription(): ?string
    {
        return $this->description;
    }
    public function setDescription(?string $description): static
    {
        $this->description = $description;
        return $this;
    }
    public function getMetadata(): ?array
    {
        return $this->metadata;
    }
    public function setMetadata(?array $metadata): static
    {
        $this->metadata = $metadata;
        return $this;
    }
    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
