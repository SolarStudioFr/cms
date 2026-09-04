<?php

namespace Plugin\News\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Plugin\News\Entity\NewsArticle;
use Plugin\News\Repository\NewsArticleRepository;

/**
 * @implements ProviderInterface<NewsArticle>
 */
class PublishedNewsArticleItemProvider implements ProviderInterface
{
    public function __construct(
        private readonly NewsArticleRepository $newsArticleRepository,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): ?NewsArticle
    {
        $id = $uriVariables['id'] ?? null;

        return null !== $id ? $this->newsArticleRepository->findOnePublishedById((int) $id) : null;
    }
}
