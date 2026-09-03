<?php

namespace App\Service;

/**
 * Scans plugin/*\/plugin.json for active plugin manifests. This is the
 * generic, reusable mechanism through which the admin frontend discovers
 * which Module Federation remotes to dynamically load - not specific to
 * the Page plugin.
 */
class PluginRegistry
{
    public function __construct(
        private readonly string $pluginDir,
    ) {
    }

    /**
     * @return list<array{name: string, label: string, remoteEntry: string, exposedModule: string}>
     */
    public function getActivePlugins(): array
    {
        $plugins = [];

        foreach (glob($this->pluginDir.'/*/plugin.json') ?: [] as $manifestPath) {
            $manifest = json_decode((string) file_get_contents($manifestPath), true);

            if (!\is_array($manifest) || !isset($manifest['name'], $manifest['label'], $manifest['remoteEntry'], $manifest['exposedModule'])) {
                continue;
            }

            $plugins[] = [
                'name' => (string) $manifest['name'],
                'label' => (string) $manifest['label'],
                'remoteEntry' => (string) $manifest['remoteEntry'],
                'exposedModule' => (string) $manifest['exposedModule'],
            ];
        }

        return $plugins;
    }
}
