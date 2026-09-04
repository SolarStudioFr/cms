<?php

namespace App\Controller\Admin;

use App\Service\PluginRegistry;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Plugin backend: GET /api/admin/plugins (enabled-only, consumed by the
 * admin frontend's dynamic Module Federation loader) plus the plugin
 * manager admin page (step 06): list every plugin with its state,
 * enable/disable, and permanently delete. Every route is already gated by
 * the ^/api/admin ROLE_SUPER_ADMIN access_control rule in security.yaml.
 */
class PluginController
{
    public function __construct(
        private readonly PluginRegistry $pluginRegistry,
    ) {
    }

    /** Enabled plugins only - what the dynamic remote loader should load. */
    #[Route('/api/admin/plugins', name: 'admin_plugins', methods: ['GET'])]
    public function list(): JsonResponse
    {
        return new JsonResponse($this->pluginRegistry->getActivePlugins());
    }

    /** Every discovered plugin, enabled or not, for the management page. */
    #[Route('/api/admin/plugins/all', name: 'admin_plugins_all', methods: ['GET'])]
    public function listAll(): JsonResponse
    {
        return new JsonResponse($this->pluginRegistry->getAllPlugins());
    }

    /** Enables or disables a plugin (JSON body: {"enabled": true|false}). */
    #[Route('/api/admin/plugins/{name}', name: 'admin_plugins_update', methods: ['PATCH'])]
    public function update(string $name, Request $request): JsonResponse
    {
        $payload = json_decode($request->getContent(), true);

        if (!\is_array($payload) || !\array_key_exists('enabled', $payload)) {
            return new JsonResponse(['error' => 'Missing "enabled" boolean in the request body.'], Response::HTTP_BAD_REQUEST);
        }

        $this->pluginRegistry->setEnabled($name, (bool) $payload['enabled']);

        return new JsonResponse(['name' => $name, 'enabled' => (bool) $payload['enabled']]);
    }

    /** Permanently deletes a plugin's directory. */
    #[Route('/api/admin/plugins/{name}', name: 'admin_plugins_delete', methods: ['DELETE'])]
    public function delete(string $name): Response
    {
        if (!$this->pluginRegistry->delete($name)) {
            return new JsonResponse(['error' => 'Plugin not found.'], Response::HTTP_NOT_FOUND);
        }

        return new Response(status: Response::HTTP_NO_CONTENT);
    }
}
