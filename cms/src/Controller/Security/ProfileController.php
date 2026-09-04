<?php

namespace App\Controller\Security;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Account editing for the currently logged-in user (step 27) - anyone with
 * a session, not just ROLE_SUPER_ADMIN. Not gated by access_control (same
 * pattern as SessionController's /api/me): the route itself is reachable
 * anonymously, but every action checks $security->getUser() and returns
 * 401 rather than acting on nothing.
 */
class ProfileController
{
    public function __construct(
        private readonly Security $security,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    #[Route('/api/profile', name: 'app_profile_update', methods: ['PATCH'])]
    public function __invoke(Request $request): JsonResponse
    {
        $user = $this->security->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['message' => 'Not authenticated.'], Response::HTTP_UNAUTHORIZED);
        }

        $payload = json_decode($request->getContent(), true) ?? [];
        $currentPassword = (string) ($payload['currentPassword'] ?? '');

        if (!$this->passwordHasher->isPasswordValid($user, $currentPassword)) {
            return new JsonResponse(['error' => 'Current password is incorrect.'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if (\array_key_exists('newPassword', $payload) && '' !== (string) $payload['newPassword']) {
            $newPassword = (string) $payload['newPassword'];
            if (\strlen($newPassword) < 8) {
                return new JsonResponse(['error' => 'New password must be at least 8 characters.'], Response::HTTP_UNPROCESSABLE_ENTITY);
            }
            $user->setPassword($this->passwordHasher->hashPassword($user, $newPassword));
        }

        $this->entityManager->flush();

        return new JsonResponse([
            'email' => $user->getEmail(),
            'roles' => $user->getRoles(),
            'verified' => $user->isVerified(),
        ]);
    }
}
