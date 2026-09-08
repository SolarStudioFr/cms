<?php

namespace Plugin\Portfolio\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use Doctrine\ORM\Mapping as ORM;
use Plugin\Portfolio\Repository\TagRepository;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\String\Slugger\AsciiSlugger;

/**
 * A portfolio tag (step 38) - many-to-many with PortfolioItem, created on
 * the fly from the admin form rather than through a dedicated management
 * page (no Patch/Delete operation: the spec only asks for "select existing
 * or create new" from the item form).
 */
#[ORM\Entity(repositoryClass: TagRepository::class)]
#[ORM\Table(name: 'portfolio_tag')]
#[ORM\UniqueConstraint(name: 'uniq_portfolio_tag_slug', columns: ['slug'])]
#[ORM\HasLifecycleCallbacks]
#[ApiResource(
    operations: [
        new GetCollection(
            uriTemplate: '/admin/portfolio/tags',
            security: "is_granted('ROLE_SUPER_ADMIN')",
        ),
        new Get(
            uriTemplate: '/admin/portfolio/tags/{id}',
            security: "is_granted('ROLE_SUPER_ADMIN')",
        ),
        new Post(
            uriTemplate: '/admin/portfolio/tags',
            security: "is_granted('ROLE_SUPER_ADMIN')",
        ),
    ],
    normalizationContext: ['groups' => ['portfolio_tag:read']],
    denormalizationContext: ['groups' => ['portfolio_tag:write']],
)]
class Tag
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['portfolio_tag:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    #[Groups(['portfolio_tag:read', 'portfolio_tag:write'])]
    private string $name = '';

    #[ORM\Column(length: 100)]
    #[Groups(['portfolio_tag:read'])]
    private string $slug = '';

    #[ORM\PrePersist]
    #[ORM\PreUpdate]
    public function refreshSlug(): void
    {
        $this->slug = (new AsciiSlugger())->slug($this->name)->lower()->toString();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }
}
