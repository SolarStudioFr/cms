<?php

namespace App\Controller\Admin;

use App\Repository\AdminMenuConfigRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Admin sidebar ordering (see AdminMenuConfig): fetch/replace the ordered
 * list of nav item keys + separators. Plain controller like SiteConfigController
 * - a wholesale array replace doesn't map to ApiPlatform CRUD semantics.
 * Already gated by the existing ^/api/admin ROLE_SUPER_ADMIN access_control rule.
 */
class AdminMenuConfigController
{
    public function __construct(
        private readonly AdminMenuConfigRepository $adminMenuConfigRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    #[Route('/api/admin/admin-menu-config', name: 'admin_menu_config_get', methods: ['GET'])]
    public function get(): JsonResponse
    {
        return new JsonResponse(['items' => $this->adminMenuConfigRepository->findOrCreate()->getItems()]);
    }

    /** Body: {"items": [{"type": "item", "key": "..."} | {"type": "separator"}, ...]} - replaces the order wholesale. */
    #[Route('/api/admin/admin-menu-config', name: 'admin_menu_config_update', methods: ['PATCH'])]
    public function update(Request $request): JsonResponse
    {
        $config = $this->adminMenuConfigRepository->findOrCreate();
        $payload = json_decode($request->getContent(), true) ?? [];

        $config->setItems(\is_array($payload['items'] ?? null) ? $payload['items'] : []);
        $config->touch();
        $this->entityManager->flush();

        return new JsonResponse(['items' => $config->getItems()]);
    }
}
