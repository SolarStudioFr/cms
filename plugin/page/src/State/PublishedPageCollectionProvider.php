<?php

namespace Plugin\Page\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Plugin\Page\Repository\PageRepository;

/**
 * @implements ProviderInterface<\Plugin\Page\Entity\Page>
 */
class PublishedPageCollectionProvider implements ProviderInterface
{
    public function __construct(
        private readonly PageRepository $pageRepository,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array
    {
        return $this->pageRepository->findPublished();
    }
}
