<?php

namespace Plugin\Stats\Entity;

use Doctrine\ORM\Mapping as ORM;
use Plugin\Stats\Repository\ClickEventRepository;

/**
 * One row per tracked click on a button/link on the public site (step 34),
 * linked to the PageView it happened on. Kept as its own table rather than
 * a counter/JSON column on PageView because a single page view can carry an
 * arbitrary number of clicks - the "extensible metrics" side of the roadmap
 * item, as opposed to PageView's fixed per-visit fields.
 */
#[ORM\Entity(repositoryClass: ClickEventRepository::class)]
#[ORM\Table(name: 'stats_click_event')]
#[ORM\Index(name: 'idx_stats_click_event_created_at', columns: ['created_at'])]
class ClickEvent
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: PageView::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?PageView $pageView = null;

    /** 'link' or 'button' - see TrackingController::click / the tracker script's findTrackedAncestor. */
    #[ORM\Column(length: 20)]
    private string $elementType = 'other';

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $label = null;

    #[ORM\Column(length: 512, nullable: true)]
    private ?string $targetUrl = null;

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

    public function getPageView(): ?PageView
    {
        return $this->pageView;
    }

    public function setPageView(?PageView $pageView): static
    {
        $this->pageView = $pageView;

        return $this;
    }

    public function getElementType(): string
    {
        return $this->elementType;
    }

    public function setElementType(string $elementType): static
    {
        $this->elementType = $elementType;

        return $this;
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function setLabel(?string $label): static
    {
        $this->label = $label;

        return $this;
    }

    public function getTargetUrl(): ?string
    {
        return $this->targetUrl;
    }

    public function setTargetUrl(?string $targetUrl): static
    {
        $this->targetUrl = $targetUrl;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
