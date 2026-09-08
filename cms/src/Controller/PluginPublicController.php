<?php

namespace App\Controller;

use App\Service\PluginRegistry;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Public, read-only list of currently enabled plugin names - the public
 * theme uses this to actually hide a disabled plugin's nav links/routes/
 * forms (App.jsx's usePlugins hook), the same way admin/plugins already
 * lets the admin SPA decide which Module Federation remotes to load.
 * Deliberately exposes only names, never remoteEntry/exposedModule (that
 * Module Federation wiring is admin-only information).
 */
class PluginPublicController
{
    public function __construct(
        private readonly PluginRegistry $pluginRegistry,
    ) {
    }

    /** @return JsonResponse list<string> of enabled plugin names */
    #[Route('/api/plugins/enabled', name: 'public_plugins_enabled', methods: ['GET'])]
    public function __invoke(): JsonResponse
    {
        $names = array_map(
            static fn (array $plugin) => $plugin['name'],
            $this->pluginRegistry->getActivePlugins(),
        );

        return new JsonResponse(array_values($names));
    }
}
