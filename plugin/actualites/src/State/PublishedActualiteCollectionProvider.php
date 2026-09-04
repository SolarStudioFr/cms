<?php

namespace Plugin\Actualites\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Plugin\Actualites\Repository\ActualiteRepository;

/**
 * @implements ProviderInterface<\Plugin\Actualites\Entity\Actualite>
 */
class PublishedActualiteCollectionProvider implements ProviderInterface
{
    public function __construct(
        private readonly ActualiteRepository $actualiteRepository,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array
    {
        return $this->actualiteRepository->findPublished();
    }
}
