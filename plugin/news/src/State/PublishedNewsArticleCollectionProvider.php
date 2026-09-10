<?php

namespace Plugin\News\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Service\ContentTranslator;
use App\Service\PluginRegistry;
use Doctrine\ORM\EntityManagerInterface;
use Plugin\News\Repository\NewsArticleRepository;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * @implements ProviderInterface<\Plugin\News\Entity\NewsArticle>
 */
class PublishedNewsArticleCollectionProvider implements ProviderInterface
{
    /** NewsArticle's own translatable fields (step 58) - same list duplicated in PublishedNewsArticleItemProvider. */
    private const TRANSLATABLE_FIELDS = ['title', 'content', 'seoTitle', 'seoDescription'];

    public function __construct(
        private readonly NewsArticleRepository $newsArticleRepository,
        private readonly PluginRegistry $pluginRegistry,
        private readonly ContentTranslator $contentTranslator,
        private readonly EntityManagerInterface $entityManager,
        private readonly RequestStack $requestStack,
    ) {
    }

    /**
     * Returns an empty list once the plugin is disabled - a disabled plugin
     * must disappear from the public site, not just the admin sidebar (see
     * App\Controller\PluginPublicController). Same non-blocking convention
     * as an unattached menu hook: nothing to show, not an error.
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array
    {
        if (!$this->pluginRegistry->isEnabled('news')) {
            return [];
        }

        $locale = $this->requestStack->getCurrentRequest()?->query->get('locale');

        return array_map(
            fn ($article) => $this->contentTranslator->translate($article, 'news_article', $article->getId(), $locale, self::TRANSLATABLE_FIELDS, $this->entityManager),
            $this->newsArticleRepository->findPublished(),
        );
    }
}
