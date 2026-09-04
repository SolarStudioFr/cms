<?php

namespace Plugin\Realisations\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Plugin\Realisations\Repository\RealisationRepository;
use Plugin\Realisations\State\PublishedRealisationCollectionProvider;
use Plugin\Realisations\State\PublishedRealisationItemProvider;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\String\Slugger\AsciiSlugger;

/**
 * A portfolio project (step 17-18), same admin/public CRUD shape as
 * Plugin\Page\Entity\Page - see that class for the reasoning behind the
 * status/slug/builderData fields - plus an optional cover image, set via
 * the shared media picker (stored as a plain URL, same convention as the
 * builder's Image/Download modules, not a File entity relation).
 */
#[ORM\Entity(repositoryClass: RealisationRepository::class)]
#[ORM\Table(name: 'realisation')]
#[ORM\UniqueConstraint(name: 'uniq_realisation_slug', columns: ['slug'])]
#[ORM\HasLifecycleCallbacks]
#[ApiResource(
    operations: [
        // Admin: full CRUD over every status.
        new GetCollection(
            uriTemplate: '/admin/realisations',
            security: "is_granted('ROLE_SUPER_ADMIN')",
        ),
        new Get(
            uriTemplate: '/admin/realisations/{id}',
            security: "is_granted('ROLE_SUPER_ADMIN')",
        ),
        new Post(
            uriTemplate: '/admin/realisations',
            security: "is_granted('ROLE_SUPER_ADMIN')",
        ),
        new Patch(
            uriTemplate: '/admin/realisations/{id}',
            security: "is_granted('ROLE_SUPER_ADMIN')",
        ),
        new Delete(
            uriTemplate: '/admin/realisations/{id}',
            security: "is_granted('ROLE_SUPER_ADMIN')",
        ),
        // Public: published realisations only, read-only.
        new GetCollection(
            uriTemplate: '/realisations',
            security: "is_granted('PUBLIC_ACCESS')",
            provider: PublishedRealisationCollectionProvider::class,
            paginationEnabled: false,
        ),
        new Get(
            uriTemplate: '/realisations/{id}',
            security: "is_granted('PUBLIC_ACCESS')",
            provider: PublishedRealisationItemProvider::class,
        ),
    ],
    normalizationContext: ['groups' => ['realisation:read']],
    denormalizationContext: ['groups' => ['realisation:write']],
)]
class Realisation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['realisation:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['realisation:read', 'realisation:write'])]
    private string $title = '';

    #[ORM\Column(type: Types::TEXT)]
    #[Groups(['realisation:read', 'realisation:write'])]
    private string $content = '';

    /** Raw builder JSON, kept alongside `content` so the builder can re-open a realisation for editing - see Plugin\Page\Entity\Page::$builderData. */
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['realisation:read', 'realisation:write'])]
    private ?string $builderData = null;

    #[ORM\Column(length: 20, enumType: RealisationStatus::class)]
    #[Groups(['realisation:read', 'realisation:write'])]
    private RealisationStatus $status = RealisationStatus::Draft;

    #[ORM\Column(length: 255)]
    #[Groups(['realisation:read'])]
    private string $slug = '';

    #[ORM\Column(length: 512, nullable: true)]
    #[Groups(['realisation:read', 'realisation:write'])]
    private ?string $coverImageUrl = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['realisation:read', 'realisation:write'])]
    private ?string $coverImageAlt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    #[Groups(['realisation:read'])]
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

    public function getStatus(): RealisationStatus
    {
        return $this->status;
    }

    public function setStatus(RealisationStatus $status): static
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
