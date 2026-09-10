<?php

namespace Plugin\Homepage\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Service\ContentTranslator;
use App\Service\PluginRegistry;
use Doctrine\ORM\EntityManagerInterface;
use Plugin\Homepage\Entity\HomeContent;
use Plugin\Homepage\Repository\HomeContentRepository;
use Symfony\Component\HttpFoundation\RequestStack;

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
    /** HomeContent's own translatable field (step 58) - no title/SEO fields, unlike Page/Portfolio/News. */
    private const TRANSLATABLE_FIELDS = ['content'];

    public function __construct(
        private readonly HomeContentRepository $homeContentRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly PluginRegistry $pluginRegistry,
        private readonly ContentTranslator $contentTranslator,
        private readonly RequestStack $requestStack,
    ) {
    }

    /**
     * Only the public `/homepage` operation is gated by the plugin's enabled
     * state - the admin get/patch operations keep working on the real
     * singleton regardless (already unreachable through the admin UI once
     * disabled, since its Module Federation remote stops loading). A
     * transient, unpersisted empty instance is returned for the disabled
     * public case rather than touching the real row: it renders as "no
     * content configured" (Home.jsx's existing fallback) without losing the
     * admin-authored content for whenever the plugin is re-enabled.
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): HomeContent
    {
        if ('/homepage' === $operation->getUriTemplate() && !$this->pluginRegistry->isEnabled('homepage')) {
            return new HomeContent();
        }

        $homeContent = $this->homeContentRepository->findSingleton();

        if (null === $homeContent) {
            $homeContent = new HomeContent();
            $this->entityManager->persist($homeContent);
            $this->entityManager->flush();
        }

        if ('/homepage' === $operation->getUriTemplate()) {
            $locale = $this->requestStack->getCurrentRequest()?->query->get('locale');
            $homeContent = $this->contentTranslator->translate($homeContent, 'homepage', (int) $homeContent->getId(), $locale, self::TRANSLATABLE_FIELDS, $this->entityManager);
        }

        return $homeContent;
    }
}
