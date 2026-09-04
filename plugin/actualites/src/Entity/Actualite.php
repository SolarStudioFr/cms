<?php

namespace Plugin\Actualites\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Plugin\Actualites\Repository\ActualiteRepository;
use Plugin\Actualites\State\PublishedActualiteCollectionProvider;
use Plugin\Actualites\State\PublishedActualiteItemProvider;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\String\Slugger\AsciiSlugger;

/**
 * A blog/news article (step 19-20), same admin/public CRUD shape as
 * Plugin\Realisations\Entity\Realisation (itself a copy of
 * Plugin\Page\Entity\Page) - see Page for the reasoning behind the
 * status/slug/builderData fields and Realisation for the cover image
 * convention (plain URL via the shared media picker, not a File relation).
 */
#[ORM\Entity(repositoryClass: ActualiteRepository::class)]
#[ORM\Table(name: 'actualite')]
#[ORM\UniqueConstraint(name: 'uniq_actualite_slug', columns: ['slug'])]
#[ORM\HasLifecycleCallbacks]
#[ApiResource(
    operations: [
        // Admin: full CRUD over every status.
        new GetCollection(
            uriTemplate: '/admin/actualites',
            security: "is_granted('ROLE_SUPER_ADMIN')",
        ),
        new Get(
            uriTemplate: '/admin/actualites/{id}',
            security: "is_granted('ROLE_SUPER_ADMIN')",
        ),
        new Post(
            uriTemplate: '/admin/actualites',
            security: "is_granted('ROLE_SUPER_ADMIN')",
        ),
        new Patch(
            uriTemplate: '/admin/actualites/{id}',
            security: "is_granted('ROLE_SUPER_ADMIN')",
        ),
        new Delete(
            uriTemplate: '/admin/actualites/{id}',
            security: "is_granted('ROLE_SUPER_ADMIN')",
        ),
        // Public: published actualites only, read-only.
        new GetCollection(
            uriTemplate: '/actualites',
            security: "is_granted('PUBLIC_ACCESS')",
            provider: PublishedActualiteCollectionProvider::class,
            paginationEnabled: false,
        ),
        new Get(
            uriTemplate: '/actualites/{id}',
            security: "is_granted('PUBLIC_ACCESS')",
            provider: PublishedActualiteItemProvider::class,
        ),
    ],
    normalizationContext: ['groups' => ['actualite:read']],
    denormalizationContext: ['groups' => ['actualite:write']],
)]
class Actualite
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['actualite:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['actualite:read', 'actualite:write'])]
    private string $title = '';

    #[ORM\Column(type: Types::TEXT)]
    #[Groups(['actualite:read', 'actualite:write'])]
    private string $content = '';

    /** Raw builder JSON, kept alongside `content` so the builder can re-open an article for editing - see Plugin\Page\Entity\Page::$builderData. */
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['actualite:read', 'actualite:write'])]
    private ?string $builderData = null;

    #[ORM\Column(length: 20, enumType: ActualiteStatus::class)]
    #[Groups(['actualite:read', 'actualite:write'])]
    private ActualiteStatus $status = ActualiteStatus::Draft;

    #[ORM\Column(length: 255)]
    #[Groups(['actualite:read'])]
    private string $slug = '';

    #[ORM\Column(length: 512, nullable: true)]
    #[Groups(['actualite:read', 'actualite:write'])]
    private ?string $coverImageUrl = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['actualite:read', 'actualite:write'])]
    private ?string $coverImageAlt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    #[Groups(['actualite:read'])]
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

    public function getStatus(): ActualiteStatus
    {
        return $this->status;
    }

    public function setStatus(ActualiteStatus $status): static
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
