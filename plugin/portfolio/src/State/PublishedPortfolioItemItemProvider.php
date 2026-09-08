<?php

namespace Plugin\Portfolio\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Service\PluginRegistry;
use Plugin\Portfolio\Entity\PortfolioItem;
use Plugin\Portfolio\Repository\PortfolioItemRepository;

/**
 * @implements ProviderInterface<PortfolioItem>
 */
class PublishedPortfolioItemItemProvider implements ProviderInterface
{
    public function __construct(
        private readonly PortfolioItemRepository $portfolioItemRepository,
        private readonly PluginRegistry $pluginRegistry,
    ) {
    }

    /** A null return already means 404 to ApiPlatform - reused for "plugin disabled", same as "not found/unpublished". */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): ?PortfolioItem
    {
        if (!$this->pluginRegistry->isEnabled('portfolio')) {
            return null;
        }

        $id = $uriVariables['id'] ?? null;

        return null !== $id ? $this->portfolioItemRepository->findOnePublishedById((int) $id) : null;
    }
}
