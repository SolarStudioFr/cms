<?php

namespace Plugin\Page\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Plugin\Page\Repository\PageRepository;
use Plugin\Page\State\PublishedPageCollectionProvider;
use Plugin\Page\State\PublishedPageItemProvider;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\String\Slugger\AsciiSlugger;

#[ORM\Entity(repositoryClass: PageRepository::class)]
#[ORM\Table(name: 'page')]
#[ORM\UniqueConstraint(name: 'uniq_page_slug', columns: ['slug'])]
#[ORM\HasLifecycleCallbacks]
#[ApiResource(
    operations: [
        // Admin: full CRUD over every status.
        new GetCollection(
            uriTemplate: '/admin/pages',
            security: "is_granted('ROLE_SUPER_ADMIN')",
        ),
        new Get(
            uriTemplate: '/admin/pages/{id}',
            security: "is_granted('ROLE_SUPER_ADMIN')",
        ),
        new Post(
            uriTemplate: '/admin/pages',
            security: "is_granted('ROLE_SUPER_ADMIN')",
        ),
        new Patch(
            uriTemplate: '/admin/pages/{id}',
            security: "is_granted('ROLE_SUPER_ADMIN')",
        ),
        new Delete(
            uriTemplate: '/admin/pages/{id}',
            security: "is_granted('ROLE_SUPER_ADMIN')",
        ),
        // Public: published pages only, read-only.
        new GetCollection(
            uriTemplate: '/pages',
            security: "is_granted('PUBLIC_ACCESS')",
            provider: PublishedPageCollectionProvider::class,
            paginationEnabled: false,
        ),
        new Get(
            uriTemplate: '/pages/{id}',
            security: "is_granted('PUBLIC_ACCESS')",
            provider: PublishedPageItemProvider::class,
        ),
    ],
    normalizationContext: ['groups' => ['page:read']],
    denormalizationContext: ['groups' => ['page:write']],
)]
class Page
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['page:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['page:read', 'page:write'])]
    private string $title = '';

    #[ORM\Column(type: Types::TEXT)]
    #[Groups(['page:read', 'page:write'])]
    private string $content = '';

    /**
     * Raw builder JSON (step 10's `{"builder": true, "modules": [...]}`),
     * kept alongside `content` so the builder can re-open a page for
     * editing. Null when the page was authored with the fallback editor
     * (step 09) instead - `content` alone is always the public-facing HTML
     * either way, so nothing downstream of this entity needs to care which
     * editor produced it.
     */
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['page:read', 'page:write'])]
    private ?string $builderData = null;

    #[ORM\Column(length: 20, enumType: PageStatus::class)]
    #[Groups(['page:read', 'page:write'])]
    private PageStatus $status = PageStatus::Draft;

    #[ORM\Column(length: 255)]
    #[Groups(['page:read'])]
    private string $slug = '';

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    #[Groups(['page:read'])]
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

    public function getStatus(): PageStatus
    {
        return $this->status;
    }

    public function setStatus(PageStatus $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
