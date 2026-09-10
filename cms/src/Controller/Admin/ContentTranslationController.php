<?php

namespace App\Controller\Admin;

use App\Entity\ContentTranslation;
use App\Repository\ContentTranslationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Admin backend for the content-translation overlay (step 58): a generic
 * key/value store (see App\Entity\ContentTranslation's docblock), shared by
 * every content type's admin form through the ContentTranslationPanel React
 * component (exposed by the admin host via Module Federation, same
 * mechanism as MediaPicker/RichTextEditor) - never a plugin-specific
 * endpoint, so this one controller serves Page/Portfolio/News/Homepage
 * alike. Already gated by the existing ^/api/admin ROLE_SUPER_ADMIN
 * access_control rule.
 */
class ContentTranslationController
{
    public function __construct(
        private readonly ContentTranslationRepository $contentTranslationRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /** Every translated field of one content entity, across every locale: {"en": {"title": "...", ...}, ...}. */
    #[Route('/api/admin/content-translations', name: 'admin_content_translations_list', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        $entityType = (string) $request->query->get('entityType', '');
        $entityId = (int) $request->query->get('entityId', 0);
        if ('' === $entityType || 0 === $entityId) {
            return new JsonResponse(['error' => 'Missing "entityType" or "entityId".'], Response::HTTP_BAD_REQUEST);
        }

        $byLocale = [];
        foreach ($this->contentTranslationRepository->findByEntity($entityType, $entityId) as $translation) {
            $byLocale[$translation->getLocale()][$translation->getField()] = $translation->getValue();
        }

        return new JsonResponse($byLocale);
    }

    /** Body: {"entityType", "entityId", "locale", "values": {"field": "value", ...}} - upserts each provided field. */
    #[Route('/api/admin/content-translations', name: 'admin_content_translations_update', methods: ['PATCH'])]
    public function update(Request $request): JsonResponse
    {
        $payload = json_decode($request->getContent(), true);
        if (!\is_array($payload)
            || !isset($payload['entityType'], $payload['entityId'], $payload['locale'], $payload['values'])
            || !\is_array($payload['values'])
        ) {
            return new JsonResponse(['error' => 'Missing "entityType", "entityId", "locale" or "values".'], Response::HTTP_BAD_REQUEST);
        }

        $entityType = (string) $payload['entityType'];
        $entityId = (int) $payload['entityId'];
        $locale = (string) $payload['locale'];

        $result = [];
        foreach ($payload['values'] as $field => $value) {
            $field = (string) $field;
            $translation = $this->contentTranslationRepository->findOneByKey($entityType, $entityId, $field, $locale)
                ?? (new ContentTranslation())->setEntityType($entityType)->setEntityId($entityId)->setField($field)->setLocale($locale);
            $translation->setValue((string) $value);
            $this->entityManager->persist($translation);
            $result[$field] = $translation->getValue();
        }
        $this->entityManager->flush();

        return new JsonResponse($result);
    }
}
