<?php

namespace App\Controller\Security;

use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

/**
 * json_login's check_path: by the time this controller runs, the
 * JsonLoginAuthenticator has already authenticated the request (or the
 * kernel never reaches here on failure - it returns a 401 automatically).
 * Unlike form_login, json_login has no default "redirect on success"
 * behavior, so a controller is required to build the success response.
 */
class LoginController
{
    #[Route('/api/login', name: 'app_login', methods: ['POST'])]
    public function __invoke(Security $security): JsonResponse
    {
        /** @var User $user */
        $user = $security->getUser();

        return new JsonResponse([
            'email' => $user->getEmail(),
            'roles' => $user->getRoles(),
        ]);
    }
}
