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
use Plugin\News\State\NewsArticleProcessor;
use Plugin\News\State\PublishedNewsArticleCollectionProvider;
use Plugin\News\State\PublishedNewsArticleItemProvider;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\String\Slugger\AsciiSlugger;

/**
 * A blog/news article (steps 19-20, plugin renamed actualites -> news), same
 * admin/public CRUD shape as Plugin\Portfolio\Entity\PortfolioItem (itself a
 * copy of App\Entity\Page) - see Page for the reasoning behind the
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
            // Restricted to digits so it never shadows a sibling static
            // route (e.g. /admin/news/categories) depending on class
            // discovery order - see Plugin\Portfolio\Entity\PortfolioItem
            // for the same fix applied after this exact collision was hit.
            requirements: ['id' => '\d+'],
            security: "is_granted('ROLE_SUPER_ADMIN')",
        ),
        new Post(
            uriTemplate: '/admin/news',
            security: "is_granted('ROLE_SUPER_ADMIN')",
            processor: NewsArticleProcessor::class,
        ),
        new Patch(
            uriTemplate: '/admin/news/{id}',
            requirements: ['id' => '\d+'],
            security: "is_granted('ROLE_SUPER_ADMIN')",
            processor: NewsArticleProcessor::class,
        ),
        new Delete(
            uriTemplate: '/admin/news/{id}',
            requirements: ['id' => '\d+'],
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
            requirements: ['id' => '\d+'],
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

    /** Raw builder JSON, kept alongside `content` so the builder can re-open an article for editing - see App\Entity\Page::$builderData. */
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

    /** S.E.O. & social fields (step 37) - all optional, override the defaults a public template would otherwise derive from title/content. Featured image reuses coverImageUrl/coverImageAlt above rather than a second duplicate field. */
    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['news:read', 'news:write'])]
    private ?string $seoTitle = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['news:read', 'news:write'])]
    private ?string $seoDescription = null;

    #[ORM\Column(length: 512, nullable: true)]
    #[Groups(['news:read', 'news:write'])]
    private ?string $ogImageUrl = null;

    #[ORM\Column(length: 20, enumType: OgType::class)]
    #[Groups(['news:read', 'news:write'])]
    private OgType $ogType = OgType::Website;

    #[ORM\Column(length: 512, nullable: true)]
    #[Groups(['news:read', 'news:write'])]
    private ?string $canonicalUrl = null;

    /**
     * Category (step 39) - many-to-one, one category per article (the spec
     * uses the singular "liste" here vs. the plural "à sélectionner" for
     * Portfolio's tags, read as many-to-one vs many-to-many). Not directly
     * ApiPlatform-Groups'd: read/write go through the virtual
     * `category`/`categoryId` accessors below, same reasoning as
     * PortfolioItem's tags/tagIds.
     */
    #[ORM\ManyToOne(targetEntity: Category::class)]
    #[ORM\JoinColumn(name: 'category_id', nullable: true, onDelete: 'SET NULL')]
    private ?Category $categoryEntity = null;

    /**
     * Write-only: category id to associate, or null for none, resolved into
     * $categoryEntity by NewsArticleProcessor. $categoryIdProvided
     * distinguishes "the request didn't mention categoryId at all" (a
     * partial PATCH like the list view's quick "archive" action, which only
     * sends `status`, must leave the category untouched) from "the request
     * explicitly set it to null" (clear the category) - both read as PHP
     * null on $categoryId alone.
     */
    #[Groups(['news:write'])]
    private ?int $categoryId = null;

    private bool $categoryIdProvided = false;

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

    public function getSeoTitle(): ?string
    {
        return $this->seoTitle;
    }

    public function setSeoTitle(?string $seoTitle): static
    {
        $this->seoTitle = $seoTitle;

        return $this;
    }

    public function getSeoDescription(): ?string
    {
        return $this->seoDescription;
    }

    public function setSeoDescription(?string $seoDescription): static
    {
        $this->seoDescription = $seoDescription;

        return $this;
    }

    public function getOgImageUrl(): ?string
    {
        return $this->ogImageUrl;
    }

    public function setOgImageUrl(?string $ogImageUrl): static
    {
        $this->ogImageUrl = $ogImageUrl;

        return $this;
    }

    public function getOgType(): OgType
    {
        return $this->ogType;
    }

    public function setOgType(OgType $ogType): static
    {
        $this->ogType = $ogType;

        return $this;
    }

    public function getCanonicalUrl(): ?string
    {
        return $this->canonicalUrl;
    }

    public function setCanonicalUrl(?string $canonicalUrl): static
    {
        $this->canonicalUrl = $canonicalUrl;

        return $this;
    }

    public function getCategoryEntity(): ?Category
    {
        return $this->categoryEntity;
    }

    public function setCategoryEntity(?Category $categoryEntity): static
    {
        $this->categoryEntity = $categoryEntity;

        return $this;
    }

    /**
     * @return array{id: int, name: string, slug: string}|null
     */
    #[Groups(['news:read'])]
    public function getCategory(): ?array
    {
        if (null === $this->categoryEntity) {
            return null;
        }

        return ['id' => $this->categoryEntity->getId(), 'name' => $this->categoryEntity->getName(), 'slug' => $this->categoryEntity->getSlug()];
    }

    public function getCategoryId(): ?int
    {
        return $this->categoryId;
    }

    public function setCategoryId(?int $categoryId): static
    {
        $this->categoryId = $categoryId;
        $this->categoryIdProvided = true;

        return $this;
    }

    public function wasCategoryIdProvided(): bool
    {
        return $this->categoryIdProvided;
    }
}
