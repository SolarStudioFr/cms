<?php

namespace Plugin\News\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Service\PluginRegistry;
use Plugin\News\Entity\NewsArticle;
use Plugin\News\Repository\NewsArticleRepository;

/**
 * @implements ProviderInterface<NewsArticle>
 */
class PublishedNewsArticleItemProvider implements ProviderInterface
{
    public function __construct(
        private readonly NewsArticleRepository $newsArticleRepository,
        private readonly PluginRegistry $pluginRegistry,
    ) {
    }

    /** A null return already means 404 to ApiPlatform - reused for "plugin disabled", same as "not found/unpublished". */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): ?NewsArticle
    {
        if (!$this->pluginRegistry->isEnabled('news')) {
            return null;
        }

        $id = $uriVariables['id'] ?? null;

        return null !== $id ? $this->newsArticleRepository->findOnePublishedById((int) $id) : null;
    }
}
