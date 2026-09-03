<?php

namespace App\Controller\Admin;

use App\Service\PluginRegistry;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

class PluginController
{
    #[Route('/api/admin/plugins', name: 'admin_plugins', methods: ['GET'])]
    public function __invoke(PluginRegistry $pluginRegistry): JsonResponse
    {
        return new JsonResponse($pluginRegistry->getActivePlugins());
    }
}
