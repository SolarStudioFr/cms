<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Entity\Menu;
use App\Repository\MenuRepository;

/**
 * @implements ProviderInterface<Menu>
 */
class PublicMenuCollectionProvider implements ProviderInterface
{
    public function __construct(
        private readonly MenuRepository $menuRepository,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array
    {
        return $this->menuRepository->findAllAssignedToHook();
    }
}
