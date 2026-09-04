<?php

namespace App\Controller\Security;

use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Confirms the link sent by RegistrationController (step 26). GET (not
 * POST) since it's meant to be clicked directly from the email - the public
 * frontend's /verify-email/:token route just calls this on mount.
 */
class EmailVerificationController
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    #[Route('/api/verify-email/{token}', name: 'app_verify_email', methods: ['GET'])]
    public function __invoke(string $token): JsonResponse
    {
        $user = $this->userRepository->findOneBy(['verificationToken' => $token]);

        if (null === $user) {
            return new JsonResponse(['error' => 'Invalid or already used verification link.'], Response::HTTP_NOT_FOUND);
        }

        $user->setVerified(true);
        $user->setVerifiedAt(new \DateTimeImmutable());
        $user->setVerificationToken(null);
        $this->entityManager->flush();

        return new JsonResponse(['verified' => true, 'email' => $user->getEmail()]);
    }
}
