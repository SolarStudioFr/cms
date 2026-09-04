<?php

namespace App\Controller\Security;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\MailService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Public self-registration (step 26), on top of the existing session-cookie
 * auth (json_login already logs any User in regardless of `verified` - see
 * User::isVerified() docblock for what that flag does/doesn't gate).
 */
class RegistrationController
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly EntityManagerInterface $entityManager,
        private readonly MailService $mailService,
        private readonly string $defaultUri,
    ) {
    }

    #[Route('/api/register', name: 'app_register', methods: ['POST'])]
    public function __invoke(Request $request): JsonResponse
    {
        $payload = json_decode($request->getContent(), true) ?? [];
        $email = trim((string) ($payload['email'] ?? ''));
        $password = (string) ($payload['password'] ?? '');

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
        $user->setRoles(['ROLE_USER']);
        $user->setPassword($this->passwordHasher->hashPassword($user, $password));
        $user->setVerificationToken(bin2hex(random_bytes(32)));

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $verifyUrl = sprintf('%s/verify-email/%s', rtrim($this->defaultUri, '/'), $user->getVerificationToken());
        $this->mailService->send(
            $email,
            'Vérifiez votre adresse email',
            sprintf('<p>Bienvenue ! Cliquez sur le lien suivant pour vérifier votre adresse email : <a href="%1$s">%1$s</a></p>', $verifyUrl),
        );

        return new JsonResponse(['email' => $email], Response::HTTP_CREATED);
    }
}
