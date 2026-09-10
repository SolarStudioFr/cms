<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Repository\PageRepository;

/**
 * @implements ProviderInterface<\App\Entity\Page>
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
