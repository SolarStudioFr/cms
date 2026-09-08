<?php

namespace Plugin\Stats\Entity;

use Doctrine\ORM\Mapping as ORM;
use Plugin\Stats\Repository\PageViewRepository;

/**
 * One row per public-site page load (or SPA route change), created by
 * TrackingController::createPageView on the initial AJAX beacon (step 34)
 * and enriched in place: TrackingController::heartbeat fills
 * timeOnPageSeconds/maxScrollPercent once the visitor leaves/hides the tab,
 * while browser/OS/device/bot fields (matomo/device-detector) and country
 * (GeoIpResolver) are resolved synchronously at creation time (step 35) from
 * data the client never needs to send. Deliberately flat/scalar rather than
 * a JSON "metrics" bag: every field here is a fixed, known metric from the
 * roadmap, and dashboard aggregation (step 36) needs to GROUP BY/AVG them in
 * SQL - a JSON blob would make that harder, not easier, for a fixed field
 * set. Extensibility for genuinely new/optional metrics later is still
 * available via ClickEvent-style side entities rather than by widening this
 * one.
 */
#[ORM\Entity(repositoryClass: PageViewRepository::class)]
#[ORM\Table(name: 'stats_page_view')]
#[ORM\Index(name: 'idx_stats_page_view_created_at', columns: ['created_at'])]
#[ORM\Index(name: 'idx_stats_page_view_url', columns: ['url'])]
class PageView
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 512)]
    private string $url = '';

    #[ORM\Column(length: 512, nullable: true)]
    private ?string $referrer = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $browserName = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $browserVersion = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $osName = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $osVersion = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $deviceType = null;

    #[ORM\Column(length: 10, nullable: true)]
    private ?string $language = null;

    #[ORM\Column(nullable: true)]
    private ?int $screenWidth = null;

    #[ORM\Column(nullable: true)]
    private ?int $screenHeight = null;

    /** ISO 3166-1 alpha-2 country code, resolved from the visitor's IP - step 35. Never store the raw IP itself, see GeoIpResolver. */
    #[ORM\Column(length: 2, nullable: true)]
    private ?string $country = null;

    #[ORM\Column]
    private bool $isBot = false;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $botName = null;

    /** Filled in by a heartbeat beacon sent on page hide/unload - null means the visitor left before one arrived. */
    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $timeOnPageSeconds = null;

    #[ORM\Column(nullable: true)]
    private ?int $maxScrollPercent = null;

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

    public function getUrl(): string
    {
        return $this->url;
    }

    public function setUrl(string $url): static
    {
        $this->url = $url;

        return $this;
    }

    public function getReferrer(): ?string
    {
        return $this->referrer;
    }

    public function setReferrer(?string $referrer): static
    {
        $this->referrer = $referrer;

        return $this;
    }

    public function getBrowserName(): ?string
    {
        return $this->browserName;
    }

    public function setBrowserName(?string $browserName): static
    {
        $this->browserName = $browserName;

        return $this;
    }

    public function getBrowserVersion(): ?string
    {
        return $this->browserVersion;
    }

    public function setBrowserVersion(?string $browserVersion): static
    {
        $this->browserVersion = $browserVersion;

        return $this;
    }

    public function getOsName(): ?string
    {
        return $this->osName;
    }

    public function setOsName(?string $osName): static
    {
        $this->osName = $osName;

        return $this;
    }

    public function getOsVersion(): ?string
    {
        return $this->osVersion;
    }

    public function setOsVersion(?string $osVersion): static
    {
        $this->osVersion = $osVersion;

        return $this;
    }

    public function getDeviceType(): ?string
    {
        return $this->deviceType;
    }

    public function setDeviceType(?string $deviceType): static
    {
        $this->deviceType = $deviceType;

        return $this;
    }

    public function getLanguage(): ?string
    {
        return $this->language;
    }

    public function setLanguage(?string $language): static
    {
        $this->language = $language;

        return $this;
    }

    public function getScreenWidth(): ?int
    {
        return $this->screenWidth;
    }

    public function setScreenWidth(?int $screenWidth): static
    {
        $this->screenWidth = $screenWidth;

        return $this;
    }

    public function getScreenHeight(): ?int
    {
        return $this->screenHeight;
    }

    public function setScreenHeight(?int $screenHeight): static
    {
        $this->screenHeight = $screenHeight;

        return $this;
    }

    public function getCountry(): ?string
    {
        return $this->country;
    }

    public function setCountry(?string $country): static
    {
        $this->country = $country;

        return $this;
    }

    public function isBot(): bool
    {
        return $this->isBot;
    }

    public function setIsBot(bool $isBot): static
    {
        $this->isBot = $isBot;

        return $this;
    }

    public function getBotName(): ?string
    {
        return $this->botName;
    }

    public function setBotName(?string $botName): static
    {
        $this->botName = $botName;

        return $this;
    }

    public function getTimeOnPageSeconds(): ?float
    {
        return $this->timeOnPageSeconds;
    }

    public function setTimeOnPageSeconds(?float $timeOnPageSeconds): static
    {
        $this->timeOnPageSeconds = $timeOnPageSeconds;

        return $this;
    }

    public function getMaxScrollPercent(): ?int
    {
        return $this->maxScrollPercent;
    }

    public function setMaxScrollPercent(?int $maxScrollPercent): static
    {
        $this->maxScrollPercent = $maxScrollPercent;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
