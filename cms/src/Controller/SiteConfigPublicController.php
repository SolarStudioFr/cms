<?php

namespace App\Controller;

use App\Repository\SiteConfigRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Public, read-only subset of SiteConfig (steps 29-31, +defaultLocale at
 * step 55) - name/logo/favicon/defaultLocale only, never the SMTP fields.
 * The public frontend fetches this once to set the page
 * title/favicon/brand at runtime (see template/default/assets/App.jsx);
 * `defaultLocale` also seeds the public/admin TranslationContext's
 * fallback locale (steps 54/56) before any per-visitor choice exists.
 */
class SiteConfigPublicController
{
    public function __construct(
        private readonly SiteConfigRepository $siteConfigRepository,
    ) {
    }

    #[Route('/api/site-config', name: 'public_site_config_get', methods: ['GET'])]
    public function __invoke(): JsonResponse
    {
        $config = $this->siteConfigRepository->findOrCreate();

        return new JsonResponse([
            'siteName' => $config->getSiteName(),
            'logoUrl' => $config->getLogoUrl(),
            'faviconUrl' => $config->getFaviconUrl(),
            'defaultLocale' => $config->getDefaultLocale(),
        ]);
    }
}
