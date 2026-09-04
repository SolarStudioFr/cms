<?php

namespace App\Entity;

use App\Repository\AdminMenuConfigRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Singleton controlling the display order (and separators) of the admin
 * backend's own left-hand navigation (`cms/assets/adm/layout/Sidebar.jsx`) -
 * a feature requested alongside steps 32/33 (public menu management), not
 * part of the original backlog. Same pattern as SiteConfig/HomeContent: a
 * single row, fetched/created by AdminMenuConfigRepository::findOrCreate(),
 * exposed through a plain controller rather than an ApiPlatform provider.
 */
#[ORM\Entity(repositoryClass: AdminMenuConfigRepository::class)]
#[ORM\Table(name: 'admin_menu_config')]
class AdminMenuConfig
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /**
     * Ordered list of `{type: 'item', key: string} | {type: 'separator'}`.
     * `key` is either a static admin page key ('dashboard', 'files', ...)
     * or `plugin:<pluginName>` for a dynamically loaded plugin's nav item -
     * see `cms/assets/adm/layout/staticNavItems.js`. Any known key missing
     * from this list is appended at its default position by the frontend -
     * the sidebar must keep working before this is ever configured, and
     * after a new plugin is installed but not yet placed in the order.
     *
     * @var list<array<string, mixed>>
     */
    #[ORM\Column(type: Types::JSON)]
    private array $items = [];

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $updatedAt;

    public function __construct()
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function touch(): static
    {
        $this->updatedAt = new \DateTimeImmutable();

        return $this;
    }
}
