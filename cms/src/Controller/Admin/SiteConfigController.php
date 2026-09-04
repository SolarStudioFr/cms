<?php

namespace App\Controller\Admin;

use App\Entity\SiteConfig;
use App\Repository\SiteConfigRepository;
use App\Service\MailService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Process\Process;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Site-wide settings admin backend (steps 29-31): general identity
 * (site name/logo/favicon - 30), SMTP settings + test-send (29), cache
 * clear button (31). Plain controller, not an ApiResource - the singleton
 * fetch-or-create, the test-mail action and the cache-clear subprocess call
 * don't map to CRUD operations. Already gated by the existing
 * ^/api/admin ROLE_SUPER_ADMIN access_control rule (except the public GET
 * below, routed outside /api/admin on purpose).
 */
class SiteConfigController
{
    public function __construct(
        private readonly SiteConfigRepository $siteConfigRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly MailService $mailService,
        private readonly KernelInterface $kernel,
    ) {
    }

    #[Route('/api/admin/site-config', name: 'admin_site_config_get', methods: ['GET'])]
    public function get(): JsonResponse
    {
        return new JsonResponse($this->serialize($this->siteConfigRepository->findOrCreate()));
    }

    #[Route('/api/admin/site-config', name: 'admin_site_config_update', methods: ['PATCH'])]
    public function update(Request $request): JsonResponse
    {
        $config = $this->siteConfigRepository->findOrCreate();
        $payload = json_decode($request->getContent(), true) ?? [];

        $strings = ['siteName' => 'setSiteName', 'logoUrl' => 'setLogoUrl', 'faviconUrl' => 'setFaviconUrl',
            'smtpHost' => 'setSmtpHost', 'smtpUser' => 'setSmtpUser', 'smtpPassword' => 'setSmtpPassword',
            'smtpEncryption' => 'setSmtpEncryption'];
        foreach ($strings as $field => $setter) {
            if (\array_key_exists($field, $payload)) {
                $config->{$setter}(null === $payload[$field] ? null : (string) $payload[$field]);
            }
        }
        if (\array_key_exists('smtpPort', $payload)) {
            $config->setSmtpPort(null === $payload['smtpPort'] ? null : (int) $payload['smtpPort']);
        }

        $config->touch();
        $this->entityManager->flush();

        return new JsonResponse($this->serialize($config));
    }

    /** Body: {"to": "someone@example.com"}. Sends using whatever SMTP settings are currently saved (see MailService::resolveDsn()). */
    #[Route('/api/admin/site-config/test-mail', name: 'admin_site_config_test_mail', methods: ['POST'])]
    public function testMail(Request $request): JsonResponse
    {
        $payload = json_decode($request->getContent(), true) ?? [];
        $to = trim((string) ($payload['to'] ?? ''));

        if (!filter_var($to, \FILTER_VALIDATE_EMAIL)) {
            return new JsonResponse(['success' => false, 'error' => 'Invalid recipient email address.'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $this->mailService->send($to, 'Email de test - Solar CMS', '<p>Ceci est un email de test envoyé depuis la configuration du site.</p>');
        } catch (TransportExceptionInterface $exception) {
            return new JsonResponse(['success' => false, 'error' => $exception->getMessage()]);
        }

        return new JsonResponse(['success' => true]);
    }

    /** Clears the Symfony application cache for the current environment (step 31). */
    #[Route('/api/admin/site-config/clear-cache', name: 'admin_site_config_clear_cache', methods: ['POST'])]
    public function clearCache(): JsonResponse
    {
        $process = new Process(
            ['php', 'bin/console', 'cache:clear', '--env='.$this->kernel->getEnvironment()],
            $this->kernel->getProjectDir(),
        );
        $process->run();

        if (!$process->isSuccessful()) {
            return new JsonResponse(['success' => false, 'error' => $process->getErrorOutput()]);
        }

        return new JsonResponse(['success' => true]);
    }

    /** @return array<string, mixed> */
    private function serialize(SiteConfig $config): array
    {
        return [
            'siteName' => $config->getSiteName(),
            'logoUrl' => $config->getLogoUrl(),
            'faviconUrl' => $config->getFaviconUrl(),
            'smtpHost' => $config->getSmtpHost(),
            'smtpPort' => $config->getSmtpPort(),
            'smtpUser' => $config->getSmtpUser(),
            'smtpPassword' => $config->getSmtpPassword(),
            'smtpEncryption' => $config->getSmtpEncryption(),
            'updatedAt' => $config->getUpdatedAt()->format(\DATE_ATOM),
        ];
    }
}
