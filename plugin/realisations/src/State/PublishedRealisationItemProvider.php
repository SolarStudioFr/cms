<?php

namespace Plugin\Realisations\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Plugin\Realisations\Entity\Realisation;
use Plugin\Realisations\Repository\RealisationRepository;

/**
 * @implements ProviderInterface<Realisation>
 */
class PublishedRealisationItemProvider implements ProviderInterface
{
    public function __construct(
        private readonly RealisationRepository $realisationRepository,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): ?Realisation
    {
        $id = $uriVariables['id'] ?? null;

        return null !== $id ? $this->realisationRepository->findOnePublishedById((int) $id) : null;
    }
}
