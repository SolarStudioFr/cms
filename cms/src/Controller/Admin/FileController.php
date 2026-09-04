<?php

namespace App\Controller\Admin;

use App\Entity\File;
use App\Entity\FileType;
use App\Repository\FileRepository;
use App\Service\FileCleanupService;
use App\Service\FileUploadService;
use App\Service\ImageReoptimizeService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Admin file manager backend: list / upload / delete (step 02) for the
 * generic File entity from step 01, plus the cleanup-unused (step 04) and
 * reoptimize-images (step 05) admin actions. Not an API Platform resource -
 * file upload needs multipart handling, so a plain controller is simplest,
 * like PluginController. Every route is already gated by the ^/api/admin
 * ROLE_SUPER_ADMIN access_control rule in security.yaml.
 */
class FileController
{
    public function __construct(
        private readonly FileRepository $fileRepository,
        private readonly FileUploadService $fileUploadService,
        private readonly FileCleanupService $fileCleanupService,
        private readonly ImageReoptimizeService $imageReoptimizeService,
    ) {
    }

    /**
     * Lists stored files, most recent first. Accepts an optional
     * comma-separated "type" query param (e.g. "?type=img" or
     * "?type=pdf,zip") so the media picker (step 03) can restrict the
     * listing to the types it was configured with.
     */
    #[Route('/api/admin/files', name: 'admin_files_list', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        $types = array_values(array_filter(array_map(
            static fn (string $value) => FileType::tryFrom(trim($value)),
            explode(',', (string) $request->query->get('type', '')),
        )));

        $files = array_map(
            fn (File $file) => $this->serialize($file),
            $this->fileRepository->findAllOrderedByCreatedAt([] !== $types ? $types : null),
        );

        return new JsonResponse($files);
    }

    /** Stores a new upload (multipart field "file", optional "name"/"description"). */
    #[Route('/api/admin/files', name: 'admin_files_upload', methods: ['POST'])]
    public function upload(Request $request): JsonResponse
    {
        $uploadedFile = $request->files->get('file');

        if (null === $uploadedFile) {
            return new JsonResponse(['error' => 'Missing "file" in the request.'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $file = $this->fileUploadService->upload(
                $uploadedFile,
                $request->request->get('name'),
                $request->request->get('description'),
            );
        } catch (\InvalidArgumentException $exception) {
            return new JsonResponse(['error' => $exception->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return new JsonResponse($this->serialize($file), Response::HTTP_CREATED);
    }

    /** Deletes a file (on-disk assets + entity). */
    #[Route('/api/admin/files/{id}', name: 'admin_files_delete', methods: ['DELETE'])]
    public function delete(int $id): Response
    {
        $file = $this->fileRepository->find($id);

        if (null === $file) {
            return new JsonResponse(['error' => 'File not found.'], Response::HTTP_NOT_FOUND);
        }

        $this->fileUploadService->remove($file);

        return new Response(status: Response::HTTP_NO_CONTENT);
    }

    /**
     * Deletes every file not reported as in use by any registered
     * FileUsageCheckerInterface (step 04).
     */
    #[Route('/api/admin/files/cleanup-unused', name: 'admin_files_cleanup_unused', methods: ['POST'])]
    public function cleanupUnused(): JsonResponse
    {
        return new JsonResponse(['deleted' => $this->fileCleanupService->removeUnused()]);
    }

    /**
     * Regenerates the webp + thumbnails of every stored image from its
     * source (step 05).
     */
    #[Route('/api/admin/files/reoptimize-images', name: 'admin_files_reoptimize_images', methods: ['POST'])]
    public function reoptimizeImages(): JsonResponse
    {
        return new JsonResponse($this->imageReoptimizeService->reoptimizeAll());
    }

    /**
     * Maps a File entity to its admin JSON representation, resolving every
     * stored path to a URL (falling back to the placeholder when missing).
     *
     * @return array<string, mixed>
     */
    private function serialize(File $file): array
    {
        return [
            'id' => $file->getId(),
            'name' => $file->getName(),
            'description' => $file->getDescription(),
            'size' => $file->getSize(),
            'width' => $file->getWidth(),
            'height' => $file->getHeight(),
            'type' => $file->getType()->value,
            'url' => $this->fileUploadService->resolveUrl($file->getFile()),
            'sourceUrl' => null !== $file->getSource() ? $this->fileUploadService->resolveUrl($file->getSource()) : null,
            'thumbnails' => array_map(
                fn (string $path) => $this->fileUploadService->resolveUrl($path),
                $file->getThumbnail(),
            ),
            'createdAt' => $file->getCreatedAt()->format(\DATE_ATOM),
            'modifiedAt' => $file->getModifiedAt()->format(\DATE_ATOM),
        ];
    }
}
