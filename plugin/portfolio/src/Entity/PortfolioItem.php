<?php

namespace Plugin\Portfolio\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Plugin\Portfolio\Repository\PortfolioItemRepository;
use Plugin\Portfolio\State\PortfolioItemProcessor;
use Plugin\Portfolio\State\PublishedPortfolioItemCollectionProvider;
use Plugin\Portfolio\State\PublishedPortfolioItemItemProvider;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\String\Slugger\AsciiSlugger;

/**
 * A portfolio project (steps 17-18, plugin renamed realisations -> portfolio),
 * same admin/public CRUD shape as App\Entity\Page - see that class
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
            requirements: ['id' => '\d+'],
            security: "is_granted('ROLE_SUPER_ADMIN')",
        ),
        new Post(
            uriTemplate: '/admin/portfolio',
            security: "is_granted('ROLE_SUPER_ADMIN')",
            processor: PortfolioItemProcessor::class,
        ),
        new Patch(
            uriTemplate: '/admin/portfolio/{id}',
            requirements: ['id' => '\d+'],
            security: "is_granted('ROLE_SUPER_ADMIN')",
            processor: PortfolioItemProcessor::class,
        ),
        new Delete(
            uriTemplate: '/admin/portfolio/{id}',
            requirements: ['id' => '\d+'],
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
            requirements: ['id' => '\d+'],
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

    /** Raw builder JSON, kept alongside `content` so the builder can re-open a portfolio item for editing - see App\Entity\Page::$builderData. */
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

    /** S.E.O. & social fields (step 37) - all optional, override the defaults a public template would otherwise derive from title/content. Featured image reuses coverImageUrl/coverImageAlt above rather than a second duplicate field. */
    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['portfolio:read', 'portfolio:write'])]
    private ?string $seoTitle = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['portfolio:read', 'portfolio:write'])]
    private ?string $seoDescription = null;

    #[ORM\Column(length: 512, nullable: true)]
    #[Groups(['portfolio:read', 'portfolio:write'])]
    private ?string $ogImageUrl = null;

    #[ORM\Column(length: 20, enumType: OgType::class)]
    #[Groups(['portfolio:read', 'portfolio:write'])]
    private OgType $ogType = OgType::Website;

    #[ORM\Column(length: 512, nullable: true)]
    #[Groups(['portfolio:read', 'portfolio:write'])]
    private ?string $canonicalUrl = null;

    /**
     * Tags (step 38) - many-to-many, created on the fly from the admin form.
     * Not directly ApiPlatform-Groups'd: read/write go through the virtual
     * `tags`/`tagIds` accessors below so the wire format is a plain array of
     * {id,name,slug} on read and a plain array of tag ids on write, instead
     * of ApiPlatform's IRI-relation format (simpler for this internal admin
     * form, and this project has no other IRI-relation precedent to match).
     */
    #[ORM\ManyToMany(targetEntity: Tag::class)]
    #[ORM\JoinTable(name: 'portfolio_item_tag')]
    private Collection $tagEntities;

    /**
     * Write-only: tag ids to associate, resolved into $tagEntities by
     * PortfolioItemProcessor. Null (the default, and what a partial PATCH
     * that omits this field leaves it at - e.g. the list view's quick
     * "archive" action) means "leave tags untouched"; an explicit empty
     * array means "clear all tags" - the two must stay distinguishable.
     */
    #[Groups(['portfolio:write'])]
    private ?array $tagIds = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    #[Groups(['portfolio:read'])]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->tagEntities = new ArrayCollection();
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

    public function getTagEntities(): Collection
    {
        return $this->tagEntities;
    }

    public function addTag(Tag $tag): static
    {
        if (!$this->tagEntities->contains($tag)) {
            $this->tagEntities->add($tag);
        }

        return $this;
    }

    public function removeTag(Tag $tag): static
    {
        $this->tagEntities->removeElement($tag);

        return $this;
    }

    /**
     * @return list<array{id: int, name: string, slug: string}>
     */
    #[Groups(['portfolio:read'])]
    public function getTags(): array
    {
        return array_values(array_map(
            static fn (Tag $tag): array => ['id' => $tag->getId(), 'name' => $tag->getName(), 'slug' => $tag->getSlug()],
            $this->tagEntities->toArray(),
        ));
    }

    /**
     * @return list<int>|null
     */
    public function getTagIds(): ?array
    {
        return $this->tagIds;
    }

    /**
     * @param list<int>|null $tagIds
     */
    public function setTagIds(?array $tagIds): static
    {
        $this->tagIds = $tagIds;

        return $this;
    }
}
