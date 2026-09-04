<?php

namespace Plugin\Portfolio\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Plugin\Portfolio\Repository\PortfolioItemRepository;

/**
 * @implements ProviderInterface<\Plugin\Portfolio\Entity\PortfolioItem>
 */
class PublishedPortfolioItemCollectionProvider implements ProviderInterface
{
    public function __construct(
        private readonly PortfolioItemRepository $portfolioItemRepository,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array
    {
        return $this->portfolioItemRepository->findPublished();
    }
}
