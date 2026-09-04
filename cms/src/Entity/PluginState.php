<?php

namespace App\Entity;

use App\Repository\PluginStateRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Persists whether a discovered plugin (plugin/*\/plugin.json, scanned by
 * PluginRegistry) is enabled or disabled. The manifest alone can't carry
 * this - it's just a static file - so PluginRegistry keeps one row per
 * plugin name it has ever seen toggled; a plugin with no row is treated
 * as enabled by default (see PluginRegistry::isEnabled()).
 */
#[ORM\Entity(repositoryClass: PluginStateRepository::class)]
#[ORM\Table(name: 'plugin_state')]
class PluginState
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100, unique: true)]
    private string $name;

    #[ORM\Column]
    private bool $enabled = true;

    public function __construct(string $name)
    {
        $this->name = $name;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function setEnabled(bool $enabled): static
    {
        $this->enabled = $enabled;

        return $this;
    }
}
