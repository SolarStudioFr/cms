<?php

namespace App\Controller;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatorBagInterface;

/**
 * Public JSON catalog endpoint for the standard Symfony translation files
 * (step 53): the admin and public React apps aren't Twig-rendered, so they
 * can't use `trans()`/`{% trans %}` directly - instead each app's
 * TranslationProvider fetches its domain's full key/value map once per
 * locale change and does lookups client-side. Public (no auth) since these
 * are plain UI strings needed before login (e.g. the Login form itself).
 */
class I18nCatalogController
{
    public function __construct(
        #[Autowire(service: 'translator')]
        private readonly TranslatorBagInterface $translator,
    ) {
    }

    /** Returns every message of one (domain, locale) pair as a flat {key: value} object. */
    #[Route('/api/i18n/{domain}/{locale}', name: 'i18n_catalog', methods: ['GET'], requirements: ['domain' => '[a-z_]+', 'locale' => '[a-z]{2}'])]
    public function __invoke(string $domain, string $locale): JsonResponse
    {
        return new JsonResponse($this->translator->getCatalogue($locale)->all($domain));
    }
}
