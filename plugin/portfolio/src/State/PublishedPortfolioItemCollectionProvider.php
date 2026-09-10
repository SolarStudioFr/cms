<?php

namespace Plugin\Portfolio\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Service\ContentTranslator;
use App\Service\PluginRegistry;
use Doctrine\ORM\EntityManagerInterface;
use Plugin\Portfolio\Repository\PortfolioItemRepository;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * @implements ProviderInterface<\Plugin\Portfolio\Entity\PortfolioItem>
 */
class PublishedPortfolioItemCollectionProvider implements ProviderInterface
{
    /** PortfolioItem's own translatable fields (step 58) - same list duplicated in PublishedPortfolioItemItemProvider. */
    private const TRANSLATABLE_FIELDS = ['title', 'content', 'seoTitle', 'seoDescription'];

    public function __construct(
        private readonly PortfolioItemRepository $portfolioItemRepository,
        private readonly PluginRegistry $pluginRegistry,
        private readonly ContentTranslator $contentTranslator,
        private readonly EntityManagerInterface $entityManager,
        private readonly RequestStack $requestStack,
    ) {
    }

    /**
     * Returns an empty list once the plugin is disabled - a disabled plugin
     * must disappear from the public site, not just the admin sidebar (see
     * App\Controller\PluginPublicController). Same non-blocking convention
     * as an unattached menu hook: nothing to show, not an error.
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array
    {
        if (!$this->pluginRegistry->isEnabled('portfolio')) {
            return [];
        }

        $locale = $this->requestStack->getCurrentRequest()?->query->get('locale');

        return array_map(
            fn ($item) => $this->contentTranslator->translate($item, 'portfolio_item', $item->getId(), $locale, self::TRANSLATABLE_FIELDS, $this->entityManager),
            $this->portfolioItemRepository->findPublished(),
        );
    }
}
