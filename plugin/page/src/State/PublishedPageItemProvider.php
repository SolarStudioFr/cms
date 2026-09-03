<?php

namespace Plugin\Page\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Plugin\Page\Entity\Page;
use Plugin\Page\Repository\PageRepository;

/**
 * @implements ProviderInterface<Page>
 */
class PublishedPageItemProvider implements ProviderInterface
{
    public function __construct(
        private readonly PageRepository $pageRepository,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): ?Page
    {
        $id = $uriVariables['id'] ?? null;

        return null !== $id ? $this->pageRepository->findOnePublishedById((int) $id) : null;
    }
}
