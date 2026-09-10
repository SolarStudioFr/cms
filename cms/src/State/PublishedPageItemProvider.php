<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Entity\Page;
use App\Repository\PageRepository;

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
