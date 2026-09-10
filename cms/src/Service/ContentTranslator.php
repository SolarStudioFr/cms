<?php

namespace App\Service;

use App\Repository\ContentTranslationRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Overlays translated field values onto a content entity for public display
 * (step 58). Generic on purpose - i18n has zero knowledge of Page,
 * PortfolioItem, NewsArticle or HomeContent; each of their own public
 * providers calls this with its own `$entityType` string and the field
 * names it wants translatable (all four use the exact same setter naming
 * convention: setTitle()/setContent()/setSeoTitle()/setSeoDescription()).
 */
class ContentTranslator
{
    public function __construct(
        private readonly ContentTranslationRepository $contentTranslationRepository,
    ) {
    }

    /**
     * Returns $entity unchanged when $locale is null/empty or nothing is
     * translated for it. Otherwise detaches $entity from the EntityManager
     * (this is a read-only overlay for one HTTP response, never meant to be
     * flushed) and calls its own setters for whichever of $fields have a
     * stored translation, leaving untranslated fields at their base value.
     *
     * @template T of object
     *
     * @param T $entity
     * @param string[] $fields translatable field names, e.g. ['title', 'content', 'seoTitle', 'seoDescription']
     *
     * @return T
     */
    public function translate(object $entity, string $entityType, int $entityId, ?string $locale, array $fields, EntityManagerInterface $entityManager): object
    {
        if (null === $locale || '' === $locale) {
            return $entity;
        }

        $translations = $this->contentTranslationRepository->findValuesForLocale($entityType, $entityId, $locale);
        if ([] === $translations) {
            return $entity;
        }

        $entityManager->detach($entity);

        foreach ($fields as $field) {
            if (isset($translations[$field])) {
                $setter = 'set'.ucfirst($field);
                $entity->{$setter}($translations[$field]);
            }
        }

        return $entity;
    }
}
