<?php

namespace Plugin\News\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Plugin\News\Repository\NewsArticleRepository;

/**
 * @implements ProviderInterface<\Plugin\News\Entity\NewsArticle>
 */
class PublishedNewsArticleCollectionProvider implements ProviderInterface
{
    public function __construct(
        private readonly NewsArticleRepository $newsArticleRepository,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array
    {
        return $this->newsArticleRepository->findPublished();
    }
}
