<?php

namespace App\Controller;

use App\Repository\SiteConfigRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Public, read-only subset of SiteConfig (steps 29-31) - name/logo/favicon
 * only, never the SMTP fields. The public frontend fetches this once to set
 * the page title/favicon/brand at runtime (see template/default/assets/App.jsx).
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
        ]);
    }
}
