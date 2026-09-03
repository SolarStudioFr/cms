<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Twig\Environment;

/**
 * Serves the public site's React SPA shell. Same catch-all pattern as
 * AdminAppController: low priority so it never shadows /api/* or /adm/*.
 */
class HomeController
{
    #[Route(
        '/{reactRouting}',
        name: 'app_index',
        requirements: ['reactRouting' => '.*'],
        defaults: ['reactRouting' => null],
        priority: -100,
    )]
    public function __invoke(Environment $twig): Response
    {
        return new Response($twig->render('@theme/base.html.twig'));
    }
}
