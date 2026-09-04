<?php

namespace Plugin\I18n\Controller\Admin;

use Doctrine\ORM\EntityManagerInterface;
use Plugin\I18n\Entity\Lang;
use Plugin\I18n\Entity\Translation;
use Plugin\I18n\Repository\LangRepository;
use Plugin\I18n\Repository\TranslationRepository;
use Plugin\I18n\Service\TranslationPoConverter;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Translation management backend (step 08): list/upsert/delete individual
 * strings for a (lang, domain) pair, plus PO export/import. Plain
 * controller like FileController - PO import needs multipart handling and
 * export returns a raw file body, both awkward as an API Platform resource.
 * Every route is already gated by the ^/api/admin ROLE_SUPER_ADMIN
 * access_control rule in security.yaml.
 */
class TranslationController
{
    public function __construct(
        private readonly LangRepository $langRepository,
        private readonly TranslationRepository $translationRepository,
        private readonly TranslationPoConverter $poConverter,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /** Lists every translation for a (lang, domain) pair. */
    #[Route('/api/admin/translations', name: 'admin_translations_list', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        $lang = $this->resolveLang((string) $request->query->get('lang', ''));
        if (null === $lang) {
            return new JsonResponse(['error' => 'Unknown lang.'], Response::HTTP_NOT_FOUND);
        }
        $domain = (string) $request->query->get('domain', 'messages');

        $translations = array_map(
            fn (Translation $t) => $this->serialize($t),
            $this->translationRepository->findByLangAndDomain($lang, $domain),
        );

        return new JsonResponse($translations);
    }

    /** Creates or updates one translation (JSON body: {"lang", "key", "value", "domain"?}). */
    #[Route('/api/admin/translations', name: 'admin_translations_upsert', methods: ['POST'])]
    public function upsert(Request $request): JsonResponse
    {
        $payload = json_decode($request->getContent(), true);
        if (!\is_array($payload) || !isset($payload['lang'], $payload['key'], $payload['value'])) {
            return new JsonResponse(['error' => 'Missing "lang", "key" or "value".'], Response::HTTP_BAD_REQUEST);
        }

        $lang = $this->resolveLang((string) $payload['lang']);
        if (null === $lang) {
            return new JsonResponse(['error' => 'Unknown lang.'], Response::HTTP_NOT_FOUND);
        }
        $domain = (string) ($payload['domain'] ?? 'messages');

        $translation = $this->translationRepository->findOneByLangDomainKey($lang, $domain, (string) $payload['key'])
            ?? (new Translation())->setLang($lang)->setDomain($domain)->setMessageKey((string) $payload['key']);
        $translation->setValue((string) $payload['value']);

        $this->entityManager->persist($translation);
        $this->entityManager->flush();

        return new JsonResponse($this->serialize($translation), Response::HTTP_CREATED);
    }

    /** Exports every translation for a (lang, domain) pair as a downloadable PO file. */
    #[Route('/api/admin/translations/export', name: 'admin_translations_export', methods: ['GET'])]
    public function export(Request $request): Response
    {
        $lang = $this->resolveLang((string) $request->query->get('lang', ''));
        if (null === $lang) {
            return new JsonResponse(['error' => 'Unknown lang.'], Response::HTTP_NOT_FOUND);
        }
        $domain = (string) $request->query->get('domain', 'messages');

        $messages = [];
        foreach ($this->translationRepository->findByLangAndDomain($lang, $domain) as $translation) {
            $messages[$translation->getMessageKey()] = $translation->getValue();
        }

        $content = $this->poConverter->export($lang->getCode(), $domain, $messages);

        return new Response($content, Response::HTTP_OK, [
            'Content-Type' => 'text/x-gettext-translation; charset=UTF-8',
            'Content-Disposition' => \sprintf('attachment; filename="%s.%s.po"', $domain, $lang->getCode()),
        ]);
    }

    /** Imports a PO file (multipart field "file", plus "lang"/"domain"), upserting every entry it contains. */
    #[Route('/api/admin/translations/import', name: 'admin_translations_import', methods: ['POST'])]
    public function import(Request $request): JsonResponse
    {
        $uploadedFile = $request->files->get('file');
        if (null === $uploadedFile) {
            return new JsonResponse(['error' => 'Missing "file" in the request.'], Response::HTTP_BAD_REQUEST);
        }

        $lang = $this->resolveLang((string) $request->request->get('lang', ''));
        if (null === $lang) {
            return new JsonResponse(['error' => 'Unknown lang.'], Response::HTTP_NOT_FOUND);
        }
        $domain = (string) $request->request->get('domain', 'messages');

        $messages = $this->poConverter->import($uploadedFile->getPathname(), $lang->getCode(), $domain);

        foreach ($messages as $key => $value) {
            $translation = $this->translationRepository->findOneByLangDomainKey($lang, $domain, $key)
                ?? (new Translation())->setLang($lang)->setDomain($domain)->setMessageKey($key);
            $translation->setValue($value);
            $this->entityManager->persist($translation);
        }
        $this->entityManager->flush();

        return new JsonResponse(['imported' => \count($messages)]);
    }

    /** Deletes one translation entry. */
    #[Route('/api/admin/translations/{id}', name: 'admin_translations_delete', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    public function delete(int $id): Response
    {
        $translation = $this->translationRepository->find($id);
        if (null === $translation) {
            return new JsonResponse(['error' => 'Translation not found.'], Response::HTTP_NOT_FOUND);
        }

        $this->entityManager->remove($translation);
        $this->entityManager->flush();

        return new Response(status: Response::HTTP_NO_CONTENT);
    }

    private function resolveLang(string $code): ?Lang
    {
        if ('' === $code) {
            return null;
        }

        return $this->langRepository->findOneBy(['code' => $code]);
    }

    /** @return array<string, mixed> */
    private function serialize(Translation $translation): array
    {
        return [
            'id' => $translation->getId(),
            'key' => $translation->getMessageKey(),
            'value' => $translation->getValue(),
            'domain' => $translation->getDomain(),
        ];
    }
}
