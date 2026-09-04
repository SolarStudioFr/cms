<?php

namespace Plugin\Portfolio\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Plugin\Portfolio\Repository\PortfolioItemRepository;
use Plugin\Portfolio\State\PublishedPortfolioItemCollectionProvider;
use Plugin\Portfolio\State\PublishedPortfolioItemItemProvider;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\String\Slugger\AsciiSlugger;

/**
 * A portfolio project (steps 17-18, plugin renamed realisations -> portfolio),
 * same admin/public CRUD shape as Plugin\Page\Entity\Page - see that class
 * for the reasoning behind the status/slug/builderData fields - plus an
 * optional cover image, set via the shared media picker (stored as a plain
 * URL, same convention as the builder's Image/Download modules, not a File
 * entity relation).
 */
#[ORM\Entity(repositoryClass: PortfolioItemRepository::class)]
#[ORM\Table(name: 'portfolio_item')]
#[ORM\UniqueConstraint(name: 'uniq_portfolio_item_slug', columns: ['slug'])]
#[ORM\HasLifecycleCallbacks]
#[ApiResource(
    operations: [
        // Admin: full CRUD over every status.
        new GetCollection(
            uriTemplate: '/admin/portfolio',
            security: "is_granted('ROLE_SUPER_ADMIN')",
        ),
        new Get(
            uriTemplate: '/admin/portfolio/{id}',
            security: "is_granted('ROLE_SUPER_ADMIN')",
        ),
        new Post(
            uriTemplate: '/admin/portfolio',
            security: "is_granted('ROLE_SUPER_ADMIN')",
        ),
        new Patch(
            uriTemplate: '/admin/portfolio/{id}',
            security: "is_granted('ROLE_SUPER_ADMIN')",
        ),
        new Delete(
            uriTemplate: '/admin/portfolio/{id}',
            security: "is_granted('ROLE_SUPER_ADMIN')",
        ),
        // Public: published items only, read-only.
        new GetCollection(
            uriTemplate: '/portfolio',
            security: "is_granted('PUBLIC_ACCESS')",
            provider: PublishedPortfolioItemCollectionProvider::class,
            paginationEnabled: false,
        ),
        new Get(
            uriTemplate: '/portfolio/{id}',
            security: "is_granted('PUBLIC_ACCESS')",
            provider: PublishedPortfolioItemItemProvider::class,
        ),
    ],
    normalizationContext: ['groups' => ['portfolio:read']],
    denormalizationContext: ['groups' => ['portfolio:write']],
)]
class PortfolioItem
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['portfolio:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['portfolio:read', 'portfolio:write'])]
    private string $title = '';

    #[ORM\Column(type: Types::TEXT)]
    #[Groups(['portfolio:read', 'portfolio:write'])]
    private string $content = '';

    /** Raw builder JSON, kept alongside `content` so the builder can re-open a portfolio item for editing - see Plugin\Page\Entity\Page::$builderData. */
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['portfolio:read', 'portfolio:write'])]
    private ?string $builderData = null;

    #[ORM\Column(length: 20, enumType: PortfolioItemStatus::class)]
    #[Groups(['portfolio:read', 'portfolio:write'])]
    private PortfolioItemStatus $status = PortfolioItemStatus::Draft;

    #[ORM\Column(length: 255)]
    #[Groups(['portfolio:read'])]
    private string $slug = '';

    #[ORM\Column(length: 512, nullable: true)]
    #[Groups(['portfolio:read', 'portfolio:write'])]
    private ?string $coverImageUrl = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['portfolio:read', 'portfolio:write'])]
    private ?string $coverImageAlt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    #[Groups(['portfolio:read'])]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    #[ORM\PrePersist]
    #[ORM\PreUpdate]
    public function refreshSlug(): void
    {
        $this->slug = (new AsciiSlugger())->slug($this->title)->lower()->toString();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function setContent(string $content): static
    {
        $this->content = $content;

        return $this;
    }

    public function getBuilderData(): ?string
    {
        return $this->builderData;
    }

    public function setBuilderData(?string $builderData): static
    {
        $this->builderData = $builderData;

        return $this;
    }

    public function getStatus(): PortfolioItemStatus
    {
        return $this->status;
    }

    public function setStatus(PortfolioItemStatus $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function getCoverImageUrl(): ?string
    {
        return $this->coverImageUrl;
    }

    public function setCoverImageUrl(?string $coverImageUrl): static
    {
        $this->coverImageUrl = $coverImageUrl;

        return $this;
    }

    public function getCoverImageAlt(): ?string
    {
        return $this->coverImageAlt;
    }

    public function setCoverImageAlt(?string $coverImageAlt): static
    {
        $this->coverImageAlt = $coverImageAlt;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
