<?php

namespace Plugin\News\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Plugin\News\Repository\NewsArticleRepository;
use Plugin\News\State\PublishedNewsArticleCollectionProvider;
use Plugin\News\State\PublishedNewsArticleItemProvider;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\String\Slugger\AsciiSlugger;

/**
 * A blog/news article (steps 19-20, plugin renamed actualites -> news), same
 * admin/public CRUD shape as Plugin\Portfolio\Entity\PortfolioItem (itself a
 * copy of Plugin\Page\Entity\Page) - see Page for the reasoning behind the
 * status/slug/builderData fields and PortfolioItem for the cover image
 * convention (plain URL via the shared media picker, not a File relation).
 */
#[ORM\Entity(repositoryClass: NewsArticleRepository::class)]
#[ORM\Table(name: 'news_article')]
#[ORM\UniqueConstraint(name: 'uniq_news_article_slug', columns: ['slug'])]
#[ORM\HasLifecycleCallbacks]
#[ApiResource(
    operations: [
        // Admin: full CRUD over every status.
        new GetCollection(
            uriTemplate: '/admin/news',
            security: "is_granted('ROLE_SUPER_ADMIN')",
        ),
        new Get(
            uriTemplate: '/admin/news/{id}',
            security: "is_granted('ROLE_SUPER_ADMIN')",
        ),
        new Post(
            uriTemplate: '/admin/news',
            security: "is_granted('ROLE_SUPER_ADMIN')",
        ),
        new Patch(
            uriTemplate: '/admin/news/{id}',
            security: "is_granted('ROLE_SUPER_ADMIN')",
        ),
        new Delete(
            uriTemplate: '/admin/news/{id}',
            security: "is_granted('ROLE_SUPER_ADMIN')",
        ),
        // Public: published articles only, read-only.
        new GetCollection(
            uriTemplate: '/news',
            security: "is_granted('PUBLIC_ACCESS')",
            provider: PublishedNewsArticleCollectionProvider::class,
            paginationEnabled: false,
        ),
        new Get(
            uriTemplate: '/news/{id}',
            security: "is_granted('PUBLIC_ACCESS')",
            provider: PublishedNewsArticleItemProvider::class,
        ),
    ],
    normalizationContext: ['groups' => ['news:read']],
    denormalizationContext: ['groups' => ['news:write']],
)]
class NewsArticle
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['news:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['news:read', 'news:write'])]
    private string $title = '';

    #[ORM\Column(type: Types::TEXT)]
    #[Groups(['news:read', 'news:write'])]
    private string $content = '';

    /** Raw builder JSON, kept alongside `content` so the builder can re-open an article for editing - see Plugin\Page\Entity\Page::$builderData. */
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['news:read', 'news:write'])]
    private ?string $builderData = null;

    #[ORM\Column(length: 20, enumType: NewsArticleStatus::class)]
    #[Groups(['news:read', 'news:write'])]
    private NewsArticleStatus $status = NewsArticleStatus::Draft;

    #[ORM\Column(length: 255)]
    #[Groups(['news:read'])]
    private string $slug = '';

    #[ORM\Column(length: 512, nullable: true)]
    #[Groups(['news:read', 'news:write'])]
    private ?string $coverImageUrl = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['news:read', 'news:write'])]
    private ?string $coverImageAlt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    #[Groups(['news:read'])]
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

    public function getStatus(): NewsArticleStatus
    {
        return $this->status;
    }

    public function setStatus(NewsArticleStatus $status): static
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
