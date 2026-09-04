<?php

namespace App\Service;

use App\Entity\PluginState;
use App\Repository\PluginStateRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Scans plugin/*\/plugin.json for plugin manifests and cross-references
 * PluginState (step 06) for their enabled/disabled status - a manifest with
 * no PluginState row yet is treated as enabled by default. This is the
 * generic, reusable mechanism through which the admin frontend discovers
 * which Module Federation remotes to dynamically load - not specific to
 * the Page plugin. Also backs the plugin manager admin page: enabling,
 * disabling and permanently deleting a plugin's directory.
 */
class PluginRegistry
{
    public function __construct(
        private readonly string $pluginDir,
        private readonly PluginStateRepository $pluginStateRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * Every discovered plugin, enabled or not - for the admin management page.
     *
     * @return list<array{name: string, label: string, remoteEntry: string, exposedModule: string, enabled: bool}>
     */
    public function getAllPlugins(): array
    {
        $plugins = [];

        foreach (glob($this->pluginDir.'/*/plugin.json') ?: [] as $manifestPath) {
            $manifest = json_decode((string) file_get_contents($manifestPath), true);

            if (!\is_array($manifest) || !isset($manifest['name'], $manifest['label'], $manifest['remoteEntry'], $manifest['exposedModule'])) {
                continue;
            }

            $name = (string) $manifest['name'];

            $plugins[] = [
                'name' => $name,
                'label' => (string) $manifest['label'],
                'remoteEntry' => (string) $manifest['remoteEntry'],
                'exposedModule' => (string) $manifest['exposedModule'],
                'enabled' => $this->isEnabled($name),
            ];
        }

        return $plugins;
    }

    /**
     * Enabled plugins only, in the shape the admin's dynamic Module
     * Federation loader (usePlugins.js) expects - a disabled plugin must
     * stop being loaded.
     *
     * @return list<array{name: string, label: string, remoteEntry: string, exposedModule: string}>
     */
    public function getActivePlugins(): array
    {
        return array_values(array_map(
            static fn (array $plugin) => [
                'name' => $plugin['name'],
                'label' => $plugin['label'],
                'remoteEntry' => $plugin['remoteEntry'],
                'exposedModule' => $plugin['exposedModule'],
            ],
            array_filter($this->getAllPlugins(), static fn (array $plugin) => $plugin['enabled']),
        ));
    }

    public function isEnabled(string $name): bool
    {
        $state = $this->pluginStateRepository->findOneByName($name);

        return null === $state || $state->isEnabled();
    }

    /** Toggles a plugin's enabled state. Silently does nothing if the plugin doesn't exist. */
    public function setEnabled(string $name, bool $enabled): void
    {
        $state = $this->pluginStateRepository->findOneByName($name) ?? new PluginState($name);
        $state->setEnabled($enabled);

        $this->entityManager->persist($state);
        $this->entityManager->flush();
    }

    /**
     * Permanently deletes a plugin's directory and its PluginState row.
     *
     * @return bool false if no such plugin (manifest) exists - name is
     *              resolved via basename() and re-checked against a real
     *              plugin.json to reject path traversal from the route param
     */
    public function delete(string $name): bool
    {
        $directory = $this->pluginDir.'/'.basename($name);

        if (!is_file($directory.'/plugin.json')) {
            return false;
        }

        $this->removeDirectory($directory);

        $state = $this->pluginStateRepository->findOneByName($name);
        if (null !== $state) {
            $this->entityManager->remove($state);
            $this->entityManager->flush();
        }

        return true;
    }

    private function removeDirectory(string $directory): void
    {
        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST,
        );

        foreach ($items as $item) {
            $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
        }

        rmdir($directory);
    }
}
