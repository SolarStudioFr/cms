<?php

namespace Plugin\News\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use Doctrine\ORM\Mapping as ORM;
use Plugin\News\Repository\CategoryRepository;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\String\Slugger\AsciiSlugger;

/**
 * A news category (step 39) - many-to-one with NewsArticle, created on the
 * fly from the admin form rather than through a dedicated management page
 * (no Patch/Delete operation: the spec only asks for "select existing or
 * create new" from the article form) - see Plugin\Portfolio\Entity\Tag for
 * the equivalent many-to-many case.
 */
#[ORM\Entity(repositoryClass: CategoryRepository::class)]
#[ORM\Table(name: 'news_category')]
#[ORM\UniqueConstraint(name: 'uniq_news_category_slug', columns: ['slug'])]
#[ORM\HasLifecycleCallbacks]
#[ApiResource(
    operations: [
        new GetCollection(
            uriTemplate: '/admin/news/categories',
            security: "is_granted('ROLE_SUPER_ADMIN')",
        ),
        new Get(
            uriTemplate: '/admin/news/categories/{id}',
            security: "is_granted('ROLE_SUPER_ADMIN')",
        ),
        new Post(
            uriTemplate: '/admin/news/categories',
            security: "is_granted('ROLE_SUPER_ADMIN')",
        ),
    ],
    normalizationContext: ['groups' => ['news_category:read']],
    denormalizationContext: ['groups' => ['news_category:write']],
)]
class Category
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['news_category:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    #[Groups(['news_category:read', 'news_category:write'])]
    private string $name = '';

    #[ORM\Column(length: 100)]
    #[Groups(['news_category:read'])]
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
