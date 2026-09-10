<?php

namespace Plugin\News\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Service\ContentTranslator;
use App\Service\PluginRegistry;
use Doctrine\ORM\EntityManagerInterface;
use Plugin\News\Entity\NewsArticle;
use Plugin\News\Repository\NewsArticleRepository;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * @implements ProviderInterface<NewsArticle>
 */
class PublishedNewsArticleItemProvider implements ProviderInterface
{
    /** NewsArticle's own translatable fields (step 58) - same list duplicated in PublishedNewsArticleCollectionProvider. */
    private const TRANSLATABLE_FIELDS = ['title', 'content', 'seoTitle', 'seoDescription'];

    public function __construct(
        private readonly NewsArticleRepository $newsArticleRepository,
        private readonly PluginRegistry $pluginRegistry,
        private readonly ContentTranslator $contentTranslator,
        private readonly EntityManagerInterface $entityManager,
        private readonly RequestStack $requestStack,
    ) {
    }

    /** A null return already means 404 to ApiPlatform - reused for "plugin disabled", same as "not found/unpublished". */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): ?NewsArticle
    {
        if (!$this->pluginRegistry->isEnabled('news')) {
            return null;
        }

        $id = $uriVariables['id'] ?? null;
        if (null === $id) {
            return null;
        }

        $article = $this->newsArticleRepository->findOnePublishedById((int) $id);
        if (null === $article) {
            return null;
        }

        $locale = $this->requestStack->getCurrentRequest()?->query->get('locale');

        return $this->contentTranslator->translate($article, 'news_article', $article->getId(), $locale, self::TRANSLATABLE_FIELDS, $this->entityManager);
    }
}
