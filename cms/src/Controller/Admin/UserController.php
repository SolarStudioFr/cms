<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Full admin user management (step 28): list, create, edit roles/verified
 * state, delete. Plain controller (not an ApiResource) - same reasoning as
 * FileController/PluginController: password hashing on create and a
 * "can't delete yourself" guard are custom enough that a hand-written
 * controller stays simpler than a custom ApiResource state processor.
 * Already gated by the existing ^/api/admin ROLE_SUPER_ADMIN access_control rule.
 */
class UserController
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly EntityManagerInterface $entityManager,
        private readonly Security $security,
    ) {
    }

    #[Route('/api/admin/users', name: 'admin_users_list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        return new JsonResponse(array_map(
            fn (User $user) => $this->serialize($user),
            $this->userRepository->findAll(),
        ));
    }

    /** Admin-created accounts are auto-verified: an admin vouches for who they create, no email round-trip needed. */
    #[Route('/api/admin/users', name: 'admin_users_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $payload = json_decode($request->getContent(), true) ?? [];
        $email = trim((string) ($payload['email'] ?? ''));
        $password = (string) ($payload['password'] ?? '');
        $roles = \is_array($payload['roles'] ?? null) ? array_map('strval', $payload['roles']) : [];

        if (!filter_var($email, \FILTER_VALIDATE_EMAIL)) {
            return new JsonResponse(['error' => 'Invalid email address.'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
        if (\strlen($password) < 8) {
            return new JsonResponse(['error' => 'Password must be at least 8 characters.'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
        if (null !== $this->userRepository->findOneBy(['email' => $email])) {
            return new JsonResponse(['error' => 'This email is already registered.'], Response::HTTP_CONFLICT);
        }

        $user = new User();
        $user->setEmail($email);
        $user->setRoles($roles);
        $user->setPassword($this->passwordHasher->hashPassword($user, $password));
        $user->setVerified(true);
        $user->setVerifiedAt(new \DateTimeImmutable());

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return new JsonResponse($this->serialize($user), Response::HTTP_CREATED);
    }

    /** Body: any of {roles: string[], verified: bool}. */
    #[Route('/api/admin/users/{id}', name: 'admin_users_update', methods: ['PATCH'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $user = $this->userRepository->find($id);
        if (null === $user) {
            return new JsonResponse(['error' => 'User not found.'], Response::HTTP_NOT_FOUND);
        }

        $payload = json_decode($request->getContent(), true) ?? [];

        if (\array_key_exists('roles', $payload) && \is_array($payload['roles'])) {
            $user->setRoles(array_map('strval', $payload['roles']));
        }
        if (\array_key_exists('verified', $payload)) {
            $user->setVerified((bool) $payload['verified']);
            $user->setVerifiedAt($user->isVerified() ? ($user->getVerifiedAt() ?? new \DateTimeImmutable()) : null);
        }

        $this->entityManager->flush();

        return new JsonResponse($this->serialize($user));
    }

    #[Route('/api/admin/users/{id}', name: 'admin_users_delete', methods: ['DELETE'])]
    public function delete(int $id): Response
    {
        $user = $this->userRepository->find($id);
        if (null === $user) {
            return new JsonResponse(['error' => 'User not found.'], Response::HTTP_NOT_FOUND);
        }

        if ($user === $this->security->getUser()) {
            return new JsonResponse(['error' => 'You cannot delete your own account.'], Response::HTTP_BAD_REQUEST);
        }

        $this->entityManager->remove($user);
        $this->entityManager->flush();

        return new Response(status: Response::HTTP_NO_CONTENT);
    }

    /** @return array{id: int, email: string, roles: list<string>, verified: bool, createdAt: string} */
    private function serialize(User $user): array
    {
        return [
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'roles' => $user->getRoles(),
            'verified' => $user->isVerified(),
            'createdAt' => $user->getCreatedAt()->format(\DATE_ATOM),
        ];
    }
}
