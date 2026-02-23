<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\UserDeviceRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserDeviceRepository::class)]
#[ORM\Table(name: 'user_devices')]
class UserDevice
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'devices')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $user = null;

    #[ORM\Column(type: 'string', length: 255)]
    private string $deviceHash = '';

    #[ORM\Column(type: 'string', length: 500, nullable: true)]
    private ?string $userAgent = null;

    #[ORM\Column(type: 'string', length: 45, nullable: true)]
    private ?string $lastIp = null;

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    private ?string $deviceName = null;

    #[ORM\Column(type: 'boolean', options: ['default' => true])]
    private bool $isTrusted = false;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $lastSeenAt = null;

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
    public function getDeviceHash(): string
    {
        return $this->deviceHash;
    }
    public function setDeviceHash(string $deviceHash): static
    {
        $this->deviceHash = $deviceHash;
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
    public function getLastIp(): ?string
    {
        return $this->lastIp;
    }
    public function setLastIp(?string $lastIp): static
    {
        $this->lastIp = $lastIp;
        return $this;
    }
    public function getDeviceName(): ?string
    {
        return $this->deviceName;
    }
    public function setDeviceName(?string $deviceName): static
    {
        $this->deviceName = $deviceName;
        return $this;
    }
    public function isTrusted(): bool
    {
        return $this->isTrusted;
    }
    public function setIsTrusted(bool $isTrusted): static
    {
        $this->isTrusted = $isTrusted;
        return $this;
    }
    public function getLastSeenAt(): ?\DateTimeImmutable
    {
        return $this->lastSeenAt;
    }
    public function setLastSeenAt(?\DateTimeImmutable $lastSeenAt): static
    {
        $this->lastSeenAt = $lastSeenAt;
        return $this;
    }
    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
