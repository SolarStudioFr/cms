<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Twig\Environment;

/**
 * Serves the admin React SPA shell. The {reactRouting} wildcard lets
 * react-router-dom handle client-side paths (e.g. /adm/pages) while this
 * single route always renders the same Twig shell. Low priority so it
 * never shadows /api/* route matching.
 */
class AdminAppController
{
    #[Route(
        '/adm/{reactRouting}',
        name: 'adm_index',
        requirements: ['reactRouting' => '.*'],
        defaults: ['reactRouting' => null],
        priority: -100,
    )]
    public function __invoke(Environment $twig): Response
    {
        return new Response($twig->render('adm.base.html.twig'));
    }
}
