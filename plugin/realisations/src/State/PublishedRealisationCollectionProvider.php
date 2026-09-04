<?php

namespace Plugin\Realisations\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Plugin\Realisations\Repository\RealisationRepository;

/**
 * @implements ProviderInterface<\Plugin\Realisations\Entity\Realisation>
 */
class PublishedRealisationCollectionProvider implements ProviderInterface
{
    public function __construct(
        private readonly RealisationRepository $realisationRepository,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array
    {
        return $this->realisationRepository->findPublished();
    }
}
