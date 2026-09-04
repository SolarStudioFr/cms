<?php

namespace App\Service;

use App\Entity\File;
use App\Entity\FileType;
use App\Repository\FileRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Backs the "re-optimize all images" admin action (step 05): regenerates
 * the webp and the 4 thumbnails of every image File from its original
 * source, in place (same filenames, so existing public URLs keep working).
 * Independent of file-usage tracking, unlike step 04 - safe to run as soon
 * as step 01 exists.
 */
class ImageReoptimizeService
{
    public function __construct(
        private readonly FileRepository $fileRepository,
        private readonly ImageProcessor $imageProcessor,
        private readonly FileUploadService $fileUploadService,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * Re-optimizes every stored image. Images whose source file is missing
     * from disk are skipped (nothing to regenerate from) and counted separately.
     *
     * @return array{reoptimized: int, skipped: int}
     */
    public function reoptimizeAll(): array
    {
        $reoptimized = 0;
        $skipped = 0;

        foreach ($this->fileRepository->findAllOrderedByCreatedAt([FileType::Image]) as $file) {
            if ($this->reoptimize($file)) {
                ++$reoptimized;
            } else {
                ++$skipped;
            }
        }

        $this->entityManager->flush();

        return ['reoptimized' => $reoptimized, 'skipped' => $skipped];
    }

    /** Regenerates one image's webp + thumbnails from its source. Returns false if the source is missing. */
    private function reoptimize(File $file): bool
    {
        if (null === $file->getSource()) {
            return false;
        }

        $sourcePath = $this->fileUploadService->toAbsolutePath($file->getSource());

        if (!is_file($sourcePath)) {
            return false;
        }

        $this->imageProcessor->toWebp($sourcePath, $this->fileUploadService->toAbsolutePath($file->getFile()));

        $thumbnailBasenamesByWidth = array_combine(
            ImageProcessor::THUMBNAIL_WIDTHS,
            array_map('basename', $file->getThumbnail()),
        );
        $thumbnailDir = \dirname($this->fileUploadService->toAbsolutePath($file->getThumbnail()[0]));
        $this->imageProcessor->generateThumbnails(
            $sourcePath,
            $thumbnailDir,
            static fn (int $width) => $thumbnailBasenamesByWidth[$width],
        );

        [$width, $height] = $this->imageProcessor->dimensions($sourcePath);
        $file->setWidth($width);
        $file->setHeight($height);

        return true;
    }
}
