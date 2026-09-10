<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Repository\LangRepository;

/**
 * @implements ProviderInterface<\App\Entity\Lang>
 */
class ActiveLangCollectionProvider implements ProviderInterface
{
    public function __construct(
        private readonly LangRepository $langRepository,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array
    {
        return $this->langRepository->findActive();
    }
}
