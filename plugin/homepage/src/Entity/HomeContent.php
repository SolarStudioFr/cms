<?php

namespace Plugin\Homepage\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Patch;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Plugin\Homepage\Repository\HomeContentRepository;
use Plugin\Homepage\State\HomeContentProvider;
use Symfony\Component\Serializer\Attribute\Groups;

/**
 * The homepage's own content (step 21) - a singleton, unlike the list-based
 * content plugins (Page, Portfolio, News): there is always exactly
 * one row, auto-created on first read by HomeContentProvider, so there's no
 * status/slug/create-or-delete lifecycle to model. `content`/`builderData`
 * follow the same contract as Plugin\Page\Entity\Page: `content` is always
 * the public-facing HTML, `builderData` is the raw builder JSON kept only
 * so the builder can re-open it for editing.
 */
#[ORM\Entity(repositoryClass: HomeContentRepository::class)]
#[ORM\Table(name: 'home_content')]
#[ApiResource(
    operations: [
        new Get(
            uriTemplate: '/admin/homepage',
            security: "is_granted('ROLE_SUPER_ADMIN')",
            provider: HomeContentProvider::class,
        ),
        new Patch(
            uriTemplate: '/admin/homepage',
            security: "is_granted('ROLE_SUPER_ADMIN')",
            provider: HomeContentProvider::class,
            // Without an {id} in the URI, ApiPlatform\Symfony\EventListener\ReadListener
            // defaults `read` to `getUriVariables() || isMethodSafe()` when left
            // null - both false for a Patch with no uriVariables - which skips
            // calling the provider entirely and denormalizes onto a brand new
            // instance instead of merging into the singleton. Force it on.
            read: true,
        ),
        new Get(
            uriTemplate: '/homepage',
            security: "is_granted('PUBLIC_ACCESS')",
            provider: HomeContentProvider::class,
        ),
    ],
    normalizationContext: ['groups' => ['home:read']],
    denormalizationContext: ['groups' => ['home:write']],
)]
class HomeContent
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['home:read'])]
    private ?int $id = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Groups(['home:read', 'home:write'])]
    private string $content = '';

    /** Raw builder JSON, kept alongside `content` so the builder can re-open the homepage for editing - see Plugin\Page\Entity\Page::$builderData. */
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['home:read', 'home:write'])]
    private ?string $builderData = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    #[Groups(['home:read'])]
    private \DateTimeImmutable $updatedAt;

    public function __construct()
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    #[ORM\PrePersist]
    #[ORM\PreUpdate]
    public function refreshUpdatedAt(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }
}
