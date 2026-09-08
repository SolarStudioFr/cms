<?php

namespace Plugin\News\State;

use ApiPlatform\Doctrine\Common\State\PersistProcessor;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Plugin\News\Entity\NewsArticle;
use Plugin\News\Repository\CategoryRepository;

/**
 * Wraps the default Doctrine persist processor to resolve NewsArticle's
 * write-only `categoryId` (step 39) into a real Category entity before
 * saving. A partial PATCH that doesn't mention `categoryId` at all (e.g.
 * the list view's quick "archive" action, which only sends `status`) must
 * leave the existing category untouched rather than clearing it - see
 * NewsArticle::$categoryIdProvided. See
 * Plugin\Portfolio\State\PortfolioItemProcessor for the many-to-many
 * equivalent.
 *
 * @implements ProcessorInterface<NewsArticle, NewsArticle>
 */
class NewsArticleProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly PersistProcessor $persistProcessor,
        private readonly CategoryRepository $categoryRepository,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        \assert($data instanceof NewsArticle);

        if ($data->wasCategoryIdProvided()) {
            $data->setCategoryEntity(
                null !== $data->getCategoryId() ? $this->categoryRepository->find($data->getCategoryId()) : null,
            );
        }

        return $this->persistProcessor->process($data, $operation, $uriVariables, $context);
    }
}
