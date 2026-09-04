<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use App\Repository\MenuRepository;
use App\State\PublicMenuCollectionProvider;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

/**
 * Admin-defined navigation menu (step 32), optionally attached to a hook
 * declared by the active template's manifest (see ThemeRegistry) - the
 * public site renders whatever menu is attached to a given hook (step 33).
 * Items (links/separators) are stored as a single JSON array rather than a
 * separate entity/table - same reasoning as the page builder's module list
 * (step 10): it's an ordered list of small typed blocks with no need to be
 * queried independently of their parent menu.
 */
#[ORM\Entity(repositoryClass: MenuRepository::class)]
#[ORM\Table(name: 'menu')]
#[ApiResource(
    operations: [
        // Admin: full CRUD, any menu (attached to a hook or not).
        new GetCollection(
            uriTemplate: '/admin/menus',
            security: "is_granted('ROLE_SUPER_ADMIN')",
        ),
        new Get(
            uriTemplate: '/admin/menus/{id}',
            security: "is_granted('ROLE_SUPER_ADMIN')",
        ),
        new Post(
            uriTemplate: '/admin/menus',
            security: "is_granted('ROLE_SUPER_ADMIN')",
        ),
        new Patch(
            uriTemplate: '/admin/menus/{id}',
            security: "is_granted('ROLE_SUPER_ADMIN')",
        ),
        new Delete(
            uriTemplate: '/admin/menus/{id}',
            security: "is_granted('ROLE_SUPER_ADMIN')",
        ),
        // Public: only menus actually attached to a hook - a menu with no
        // hookName is admin-side work in progress, not meant to be rendered.
        new GetCollection(
            uriTemplate: '/menus',
            security: "is_granted('PUBLIC_ACCESS')",
            provider: PublicMenuCollectionProvider::class,
            paginationEnabled: false,
        ),
    ],
    normalizationContext: ['groups' => ['menu:read']],
    denormalizationContext: ['groups' => ['menu:write']],
)]
class Menu
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['menu:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['menu:read', 'menu:write'])]
    private string $name = '';

    /** Name of the template hook this menu is attached to, or null while unattached. See ThemeRegistry. */
    #[ORM\Column(length: 100, nullable: true)]
    #[Groups(['menu:read', 'menu:write'])]
    private ?string $hookName = null;

    /**
     * Ordered list of `{id, type: 'link'|'separator', label?, url?, target?}`.
     * A separator entry only carries `id`/`type` - it's a visual divider,
     * not a navigable item.
     *
     * @var list<array<string, mixed>>
     */
    #[ORM\Column(type: Types::JSON)]
    #[Groups(['menu:read', 'menu:write'])]
    private array $items = [];

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    #[Groups(['menu:read'])]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
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

    public function getHookName(): ?string
    {
        return $this->hookName;
    }

    public function setHookName(?string $hookName): static
    {
        $this->hookName = $hookName;

        return $this;
    }

    /** @return list<array<string, mixed>> */
    public function getItems(): array
    {
        return $this->items;
    }

    /** @param list<array<string, mixed>> $items */
    public function setItems(array $items): static
    {
        $this->items = $items;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
