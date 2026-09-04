<?php

namespace App\Entity;

use App\Repository\SiteConfigRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Site-wide settings (steps 29-31): general identity (name/logo/favicon),
 * SMTP overrides for MailService, nothing for the cache-clear button (step
 * 31 - it doesn't need persisted state). Singleton, same pattern as
 * Plugin\Homepage\Entity\HomeContent - a single row, fetched/created by
 * SiteConfigRepository::findOrCreate() rather than an ApiPlatform provider,
 * since this entity is exposed through a plain controller (see
 * SiteConfigController's docblock).
 */
#[ORM\Entity(repositoryClass: SiteConfigRepository::class)]
#[ORM\Table(name: 'site_config')]
class SiteConfig
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private string $siteName = 'Solar CMS';

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $logoUrl = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $faviconUrl = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $smtpHost = null;

    #[ORM\Column(nullable: true)]
    private ?int $smtpPort = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $smtpUser = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $smtpPassword = null;

    /** Display-only (tls/ssl/none): Symfony's smtp transport infers TLS from the port, see MailService::resolveDsn(). */
    #[ORM\Column(length: 20, nullable: true)]
    private ?string $smtpEncryption = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $updatedAt;

    public function __construct()
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSiteName(): string
    {
        return $this->siteName;
    }

    public function setSiteName(string $siteName): static
    {
        $this->siteName = $siteName;

        return $this;
    }

    public function getLogoUrl(): ?string
    {
        return $this->logoUrl;
    }

    public function setLogoUrl(?string $logoUrl): static
    {
        $this->logoUrl = $logoUrl;

        return $this;
    }

    public function getFaviconUrl(): ?string
    {
        return $this->faviconUrl;
    }

    public function setFaviconUrl(?string $faviconUrl): static
    {
        $this->faviconUrl = $faviconUrl;

        return $this;
    }

    public function getSmtpHost(): ?string
    {
        return $this->smtpHost;
    }

    public function setSmtpHost(?string $smtpHost): static
    {
        $this->smtpHost = $smtpHost;

        return $this;
    }

    public function getSmtpPort(): ?int
    {
        return $this->smtpPort;
    }

    public function setSmtpPort(?int $smtpPort): static
    {
        $this->smtpPort = $smtpPort;

        return $this;
    }

    public function getSmtpUser(): ?string
    {
        return $this->smtpUser;
    }

    public function setSmtpUser(?string $smtpUser): static
    {
        $this->smtpUser = $smtpUser;

        return $this;
    }

    public function getSmtpPassword(): ?string
    {
        return $this->smtpPassword;
    }

    public function setSmtpPassword(?string $smtpPassword): static
    {
        $this->smtpPassword = $smtpPassword;

        return $this;
    }

    public function getSmtpEncryption(): ?string
    {
        return $this->smtpEncryption;
    }

    public function setSmtpEncryption(?string $smtpEncryption): static
    {
        $this->smtpEncryption = $smtpEncryption;

        return $this;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function touch(): static
    {
        $this->updatedAt = new \DateTimeImmutable();

        return $this;
    }
}
