<?php

namespace App\Controller\Admin;

use App\Service\ThemeRegistry;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Exposes the active template's declared hooks (see ThemeRegistry) so the
 * admin Menu form (step 32) can offer them as attachment targets. Plain
 * controller, gated by the existing ^/api/admin ROLE_SUPER_ADMIN access_control rule.
 */
class ThemeController
{
    public function __construct(
        private readonly ThemeRegistry $themeRegistry,
    ) {
    }

    #[Route('/api/admin/theme/hooks', name: 'admin_theme_hooks', methods: ['GET'])]
    public function hooks(): JsonResponse
    {
        return new JsonResponse($this->themeRegistry->getHooks());
    }
}
