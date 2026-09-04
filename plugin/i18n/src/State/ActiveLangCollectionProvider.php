<?php

namespace Plugin\I18n\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Plugin\I18n\Repository\LangRepository;

/**
 * @implements ProviderInterface<\Plugin\I18n\Entity\Lang>
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
