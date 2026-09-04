<?php

namespace Plugin\Portfolio\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Plugin\Portfolio\Entity\PortfolioItem;
use Plugin\Portfolio\Repository\PortfolioItemRepository;

/**
 * @implements ProviderInterface<PortfolioItem>
 */
class PublishedPortfolioItemItemProvider implements ProviderInterface
{
    public function __construct(
        private readonly PortfolioItemRepository $portfolioItemRepository,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): ?PortfolioItem
    {
        $id = $uriVariables['id'] ?? null;

        return null !== $id ? $this->portfolioItemRepository->findOnePublishedById((int) $id) : null;
    }
}
