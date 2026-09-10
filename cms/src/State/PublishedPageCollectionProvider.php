<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Repository\PageRepository;
use App\Service\ContentTranslator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * @implements ProviderInterface<\App\Entity\Page>
 */
class PublishedPageCollectionProvider implements ProviderInterface
{
    /** Page's own translatable fields (step 58) - same list duplicated in PublishedPageItemProvider. */
    private const TRANSLATABLE_FIELDS = ['title', 'content', 'seoTitle', 'seoDescription'];

    public function __construct(
        private readonly PageRepository $pageRepository,
        private readonly ContentTranslator $contentTranslator,
        private readonly EntityManagerInterface $entityManager,
        private readonly RequestStack $requestStack,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array
    {
        $locale = $this->requestStack->getCurrentRequest()?->query->get('locale');

        return array_map(
            fn ($page) => $this->contentTranslator->translate($page, 'page', $page->getId(), $locale, self::TRANSLATABLE_FIELDS, $this->entityManager),
            $this->pageRepository->findPublished(),
        );
    }
}
