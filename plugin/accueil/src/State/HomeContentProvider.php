<?php

namespace Plugin\Accueil\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Doctrine\ORM\EntityManagerInterface;
use Plugin\Accueil\Entity\HomeContent;
use Plugin\Accueil\Repository\HomeContentRepository;

/**
 * Backs every HomeContent operation (admin get/patch, public get) - there is
 * never an {id} in the URL, since the homepage content is a singleton: this
 * provider ignores $uriVariables and returns the one existing row, creating
 * an empty one on first read (e.g. before any admin has ever saved the
 * homepage) so a Patch always has something to merge into.
 *
 * @implements ProviderInterface<HomeContent>
 */
class HomeContentProvider implements ProviderInterface
{
    public function __construct(
        private readonly HomeContentRepository $homeContentRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): HomeContent
    {
        $homeContent = $this->homeContentRepository->findSingleton();

        if (null === $homeContent) {
            $homeContent = new HomeContent();
            $this->entityManager->persist($homeContent);
            $this->entityManager->flush();
        }

        return $homeContent;
    }
}
