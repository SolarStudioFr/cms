<?php

namespace App\Service;

/**
 * Reads the active theme's manifest (`template/<active>/theme.json`) for
 * the hooks it declares (step 32) - the points in its templates/components
 * where an admin-configured Menu can be attached (e.g. "header-menu",
 * "footer-menu"). The active theme name is hardcoded to "default" for now,
 * same as `twig.yaml`'s `@theme` namespace path (see CLAUDE.md) - both will
 * need to become configurable together once an active-theme setting exists.
 */
class ThemeRegistry
{
    public function __construct(
        private readonly string $templateDir,
        private readonly string $activeTheme,
    ) {
    }

    /** @return list<array{name: string, label: string}> */
    public function getHooks(): array
    {
        $manifestPath = $this->templateDir.'/'.$this->activeTheme.'/theme.json';

        if (!is_file($manifestPath)) {
            return [];
        }

        $manifest = json_decode((string) file_get_contents($manifestPath), true);
        $hooks = \is_array($manifest['hooks'] ?? null) ? $manifest['hooks'] : [];

        $result = [];
        foreach ($hooks as $hook) {
            if (\is_array($hook) && isset($hook['name'], $hook['label'])) {
                $result[] = ['name' => (string) $hook['name'], 'label' => (string) $hook['label']];
            }
        }

        return $result;
    }
}
