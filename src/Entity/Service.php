<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\ServiceRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ServiceRepository::class)]
#[ORM\Table(name: 'services')]
#[ORM\HasLifecycleCallbacks]
class Service
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 100)]
    private string $name = '';

    #[ORM\Column(type: 'string', length: 100, unique: true)]
    private string $slug = '';

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $icon = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    private string $monthlyPrice = '0.00';

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    private string $annualPrice = '0.00';

    #[ORM\Column(type: 'string', length: 150, nullable: true)]
    private ?string $whopMonthlyPlanId = null;

    #[ORM\Column(type: 'string', length: 150, nullable: true)]
    private ?string $whopMonthlyPlanIdSandbox = null;

    #[ORM\Column(type: 'string', length: 150, nullable: true)]
    private ?string $whopAnnualPlanId = null;

    #[ORM\Column(type: 'string', length: 150, nullable: true)]
    private ?string $whopAnnualPlanIdSandbox = null;

    #[ORM\Column(type: 'boolean', options: ['default' => true])]
    private bool $isActive = true;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $features = null;

    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    private ?string $badgeText = null;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    private int $sortOrder = 0;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\OneToMany(targetEntity: Subscription::class, mappedBy: 'service')]
    private Collection $subscriptions;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->subscriptions = new ArrayCollection();
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }
    public function getName(): string
    {
        return $this->name;
    }
    public function setName(string $name): static
    {
        $this->name = $name;
        return $this;
    }
    public function getSlug(): string
    {
        return $this->slug;
    }
    public function setSlug(string $slug): static
    {
        $this->slug = $slug;
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
    public function getIcon(): ?string
    {
        return $this->icon;
    }
    public function setIcon(?string $icon): static
    {
        $this->icon = $icon;
        return $this;
    }
    public function getMonthlyPrice(): string
    {
        return $this->monthlyPrice;
    }
    public function setMonthlyPrice(string $monthlyPrice): static
    {
        $this->monthlyPrice = $monthlyPrice;
        return $this;
    }
    public function getAnnualPrice(): string
    {
        return $this->annualPrice;
    }
    public function setAnnualPrice(string $annualPrice): static
    {
        $this->annualPrice = $annualPrice;
        return $this;
    }
    public function getWhopMonthlyPlanId(): ?string
    {
        return $this->whopMonthlyPlanId;
    }
    public function setWhopMonthlyPlanId(?string $id): static
    {
        $this->whopMonthlyPlanId = $id;
        return $this;
    }
    public function getWhopMonthlyPlanIdSandbox(): ?string
    {
        return $this->whopMonthlyPlanIdSandbox;
    }
    public function setWhopMonthlyPlanIdSandbox(?string $id): static
    {
        $this->whopMonthlyPlanIdSandbox = $id;
        return $this;
    }
    public function getWhopAnnualPlanId(): ?string
    {
        return $this->whopAnnualPlanId;
    }
    public function setWhopAnnualPlanId(?string $id): static
    {
        $this->whopAnnualPlanId = $id;
        return $this;
    }
    public function getWhopAnnualPlanIdSandbox(): ?string
    {
        return $this->whopAnnualPlanIdSandbox;
    }
    public function setWhopAnnualPlanIdSandbox(?string $id): static
    {
        $this->whopAnnualPlanIdSandbox = $id;
        return $this;
    }
    public function isActive(): bool
    {
        return $this->isActive;
    }
    public function setIsActive(bool $isActive): static
    {
        $this->isActive = $isActive;
        return $this;
    }
    public function getFeatures(): ?array
    {
        return $this->features;
    }
    public function setFeatures(?array $features): static
    {
        $this->features = $features;
        return $this;
    }
    public function getBadgeText(): ?string
    {
        return $this->badgeText;
    }
    public function setBadgeText(?string $badgeText): static
    {
        $this->badgeText = $badgeText;
        return $this;
    }
    public function getSortOrder(): int
    {
        return $this->sortOrder;
    }
    public function setSortOrder(int $sortOrder): static
    {
        $this->sortOrder = $sortOrder;
        return $this;
    }
    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
    public function getSubscriptions(): Collection
    {
        return $this->subscriptions;
    }

    public function getAnnualSavingsPercent(): int
    {
        if ((float) $this->monthlyPrice <= 0)
            return 0;
        $monthlyTotal = (float) $this->monthlyPrice * 12;
        $annual = (float) $this->annualPrice;
        return (int) round((($monthlyTotal - $annual) / $monthlyTotal) * 100);
    }
}
