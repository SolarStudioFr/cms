<?php

namespace Plugin\Portfolio\State;

use ApiPlatform\Doctrine\Common\State\PersistProcessor;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Plugin\Portfolio\Entity\PortfolioItem;
use Plugin\Portfolio\Repository\TagRepository;

/**
 * Wraps the default Doctrine persist processor to resolve PortfolioItem's
 * write-only `tagIds` (step 38) into real Tag entities before saving.
 * `tagIds` is null unless the request body actually included that key - a
 * partial PATCH that doesn't mention tags (e.g. the list view's quick
 * "archive" action, which only sends `status`) must leave existing tags
 * untouched rather than wiping them.
 *
 * @implements ProcessorInterface<PortfolioItem, PortfolioItem>
 */
class PortfolioItemProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly PersistProcessor $persistProcessor,
        private readonly TagRepository $tagRepository,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        \assert($data instanceof PortfolioItem);

        if (null !== $data->getTagIds()) {
            foreach ($data->getTagEntities()->toArray() as $existingTag) {
                $data->removeTag($existingTag);
            }

            foreach ($this->tagRepository->findBy(['id' => $data->getTagIds()]) as $tag) {
                $data->addTag($tag);
            }
        }

        return $this->persistProcessor->process($data, $operation, $uriVariables, $context);
    }
}
