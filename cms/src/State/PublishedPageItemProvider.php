<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Entity\Page;
use App\Repository\PageRepository;
use App\Service\ContentTranslator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * @implements ProviderInterface<Page>
 */
class PublishedPageItemProvider implements ProviderInterface
{
    /** Page's own translatable fields (step 58) - same list duplicated in PublishedPageCollectionProvider. */
    private const TRANSLATABLE_FIELDS = ['title', 'content', 'seoTitle', 'seoDescription'];

    public function __construct(
        private readonly PageRepository $pageRepository,
        private readonly ContentTranslator $contentTranslator,
        private readonly EntityManagerInterface $entityManager,
        private readonly RequestStack $requestStack,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): ?Page
    {
        $id = $uriVariables['id'] ?? null;
        if (null === $id) {
            return null;
        }

        $page = $this->pageRepository->findOnePublishedById((int) $id);
        if (null === $page) {
            return null;
        }

        $locale = $this->requestStack->getCurrentRequest()?->query->get('locale');

        return $this->contentTranslator->translate($page, 'page', $page->getId(), $locale, self::TRANSLATABLE_FIELDS, $this->entityManager);
    }
}
