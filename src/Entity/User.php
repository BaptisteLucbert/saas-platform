<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Scheb\TwoFactorBundle\Model\Google\TwoFactorInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: 'users')]
#[ORM\HasLifecycleCallbacks]
#[UniqueEntity(fields: ['email'], message: 'Cet email est déjà utilisé.')]
class User implements UserInterface, PasswordAuthenticatedUserInterface, TwoFactorInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 180, unique: true)]
    #[Assert\NotBlank]
    #[Assert\Email]
    private string $email = '';

    #[ORM\Column(type: 'json')]
    private array $roles = [];

    #[ORM\Column(type: 'string')]
    private string $password = '';

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    private ?string $firstName = null;

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    private ?string $lastName = null;


    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $isActive = false;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $isSuspended = false;

    #[ORM\Column(type: 'boolean', options: ['default' => true])]
    private bool $mustChangePassword = true;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $twoFactorEnabled = false;

    #[ORM\Column(type: 'string', length: 32, nullable: true)]
    private ?string $twoFactorSecret = null;

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $referralCode = null;

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $referredBy = null;

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $discordId = null;

    #[ORM\Column(type: 'string', length: 150, nullable: true)]
    private ?string $whopUserId = null;

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $discordUsername = null;

    #[ORM\Column(type: 'json')]
    private array $preferences = ['email_alerts' => true, 'discord_alerts' => true, 'dark_mode' => false];

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $lastLoginAt = null;

    #[ORM\Column(type: 'string', length: 45, nullable: true)]
    private ?string $lastLoginIp = null;

    #[ORM\OneToMany(targetEntity: Subscription::class, mappedBy: 'user', cascade: ['persist', 'remove'])]
    private Collection $subscriptions;

    #[ORM\OneToMany(targetEntity: PasswordResetToken::class, mappedBy: 'user', cascade: ['persist', 'remove'])]
    private Collection $passwordResetTokens;

    #[ORM\OneToMany(targetEntity: LoginAttempt::class, mappedBy: 'user', cascade: ['persist', 'remove'])]
    private Collection $loginAttempts;

    #[ORM\OneToMany(targetEntity: AuditLog::class, mappedBy: 'user', cascade: ['persist', 'remove'])]
    private Collection $auditLogs;

    #[ORM\OneToMany(targetEntity: UserDevice::class, mappedBy: 'user', cascade: ['persist', 'remove'])]
    private Collection $devices;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->subscriptions = new ArrayCollection();
        $this->passwordResetTokens = new ArrayCollection();
        $this->loginAttempts = new ArrayCollection();
        $this->auditLogs = new ArrayCollection();
        $this->devices = new ArrayCollection();
        $this->referralCode = $this->generateReferralCode();
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    private function generateReferralCode(): string
    {
        return strtoupper(substr(md5(uniqid('', true)), 0, 8));
    }

    public function getId(): ?int
    {
        return $this->id;
    }
    public function getEmail(): string
    {
        return $this->email;
    }
    public function setEmail(string $email): static
    {
        $this->email = $email;
        return $this;
    }
    public function getUserIdentifier(): string
    {
        return $this->email;
    }

    public function getRoles(): array
    {
        $roles = $this->roles;
        $roles[] = 'ROLE_USER';
        return array_unique($roles);
    }

    public function setRoles(array $roles): static
    {
        $this->roles = $roles;
        return $this;
    }
    public function getPassword(): string
    {
        return $this->password;
    }
    public function setPassword(string $password): static
    {
        $this->password = $password;
        return $this;
    }
    public function eraseCredentials(): void
    {
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }
    public function setFirstName(?string $firstName): static
    {
        $this->firstName = $firstName;
        return $this;
    }
    public function getLastName(): ?string
    {
        return $this->lastName;
    }
    public function setLastName(?string $lastName): static
    {
        $this->lastName = $lastName;
        return $this;
    }
    public function getFullName(): string
    {
        return trim(($this->firstName ?? '') . ' ' . ($this->lastName ?? ''));
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
    public function isSuspended(): bool
    {
        return $this->isSuspended;
    }
    public function setIsSuspended(bool $isSuspended): static
    {
        $this->isSuspended = $isSuspended;
        return $this;
    }
    public function isMustChangePassword(): bool
    {
        return $this->mustChangePassword;
    }
    public function setMustChangePassword(bool $mustChangePassword): static
    {
        $this->mustChangePassword = $mustChangePassword;
        return $this;
    }
    public function isTwoFactorEnabled(): bool
    {
        return $this->twoFactorEnabled;
    }
    public function setTwoFactorEnabled(bool $twoFactorEnabled): static
    {
        $this->twoFactorEnabled = $twoFactorEnabled;
        return $this;
    }
    public function getTwoFactorSecret(): ?string
    {
        return $this->twoFactorSecret;
    }
    public function setTwoFactorSecret(?string $twoFactorSecret): static
    {
        $this->twoFactorSecret = $twoFactorSecret;
        return $this;
    }
    public function getReferralCode(): ?string
    {
        return $this->referralCode;
    }
    public function getReferredBy(): ?string
    {
        return $this->referredBy;
    }
    public function setReferredBy(?string $referredBy): static
    {
        $this->referredBy = $referredBy;
        return $this;
    }
    public function getDiscordId(): ?string
    {
        return $this->discordId;
    }
    public function setDiscordId(?string $discordId): static
    {
        $this->discordId = $discordId;
        return $this;
    }
    public function getWhopUserId(): ?string
    {
        return $this->whopUserId;
    }
    public function setWhopUserId(?string $whopUserId): static
    {
        $this->whopUserId = $whopUserId;
        return $this;
    }
    public function getDiscordUsername(): ?string
    {
        return $this->discordUsername;
    }
    public function setDiscordUsername(?string $discordUsername): static
    {
        $this->discordUsername = $discordUsername;
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
    public function getLastLoginAt(): ?\DateTimeImmutable
    {
        return $this->lastLoginAt;
    }
    public function setLastLoginAt(?\DateTimeImmutable $lastLoginAt): static
    {
        $this->lastLoginAt = $lastLoginAt;
        return $this;
    }
    public function getLastLoginIp(): ?string
    {
        return $this->lastLoginIp;
    }
    public function setLastLoginIp(?string $lastLoginIp): static
    {
        $this->lastLoginIp = $lastLoginIp;
        return $this;
    }
    public function getSubscriptions(): Collection
    {
        return $this->subscriptions;
    }

    public function getPreferences(): array
    {
        return $this->preferences;
    }

    public function setPreferences(array $preferences): static
    {
        $this->preferences = $preferences;
        return $this;
    }

    public function getPreference(string $key, mixed $default = null): mixed
    {
        return $this->preferences[$key] ?? $default;
    }

    public function getDevices(): Collection
    {
        return $this->devices;
    }

    public function getActiveSubscriptions(): Collection
    {
        return $this->subscriptions->filter(fn(Subscription $s) => $s->isActive());
    }

    public function hasActiveSubscriptionForService(string $serviceSlug): bool
    {
        return $this->getActiveSubscriptions()->exists(
            fn($key, Subscription $s) => $s->getService()?->getSlug() === $serviceSlug
        );
    }

    public function isAdmin(): bool
    {
        return in_array('ROLE_ADMIN', $this->getRoles(), true);
    }

    public function isGoogleAuthenticatorEnabled(): bool
    {
        return $this->twoFactorEnabled;
    }

    public function getGoogleAuthenticatorUsername(): string
    {
        return $this->email;
    }

    public function getGoogleAuthenticatorSecret(): ?string
    {
        return $this->twoFactorSecret;
    }
}
