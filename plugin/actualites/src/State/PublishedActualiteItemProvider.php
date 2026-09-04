<?php

namespace Plugin\Actualites\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Plugin\Actualites\Entity\Actualite;
use Plugin\Actualites\Repository\ActualiteRepository;

/**
 * @implements ProviderInterface<Actualite>
 */
class PublishedActualiteItemProvider implements ProviderInterface
{
    public function __construct(
        private readonly ActualiteRepository $actualiteRepository,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): ?Actualite
    {
        $id = $uriVariables['id'] ?? null;

        return null !== $id ? $this->actualiteRepository->findOnePublishedById((int) $id) : null;
    }
}
