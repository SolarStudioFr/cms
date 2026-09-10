<?php

namespace Plugin\Portfolio\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Service\ContentTranslator;
use App\Service\PluginRegistry;
use Doctrine\ORM\EntityManagerInterface;
use Plugin\Portfolio\Entity\PortfolioItem;
use Plugin\Portfolio\Repository\PortfolioItemRepository;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * @implements ProviderInterface<PortfolioItem>
 */
class PublishedPortfolioItemItemProvider implements ProviderInterface
{
    /** PortfolioItem's own translatable fields (step 58) - same list duplicated in PublishedPortfolioItemCollectionProvider. */
    private const TRANSLATABLE_FIELDS = ['title', 'content', 'seoTitle', 'seoDescription'];

    public function __construct(
        private readonly PortfolioItemRepository $portfolioItemRepository,
        private readonly PluginRegistry $pluginRegistry,
        private readonly ContentTranslator $contentTranslator,
        private readonly EntityManagerInterface $entityManager,
        private readonly RequestStack $requestStack,
    ) {
    }

    /** A null return already means 404 to ApiPlatform - reused for "plugin disabled", same as "not found/unpublished". */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): ?PortfolioItem
    {
        if (!$this->pluginRegistry->isEnabled('portfolio')) {
            return null;
        }

        $id = $uriVariables['id'] ?? null;
        if (null === $id) {
            return null;
        }

        $item = $this->portfolioItemRepository->findOnePublishedById((int) $id);
        if (null === $item) {
            return null;
        }

        $locale = $this->requestStack->getCurrentRequest()?->query->get('locale');

        return $this->contentTranslator->translate($item, 'portfolio_item', $item->getId(), $locale, self::TRANSLATABLE_FIELDS, $this->entityManager);
    }
}
