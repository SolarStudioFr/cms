<?php

namespace Plugin\News\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Service\PluginRegistry;
use Plugin\News\Repository\NewsArticleRepository;

/**
 * @implements ProviderInterface<\Plugin\News\Entity\NewsArticle>
 */
class PublishedNewsArticleCollectionProvider implements ProviderInterface
{
    public function __construct(
        private readonly NewsArticleRepository $newsArticleRepository,
        private readonly PluginRegistry $pluginRegistry,
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
        if (!$this->pluginRegistry->isEnabled('news')) {
            return [];
        }

        return $this->newsArticleRepository->findPublished();
    }
}
