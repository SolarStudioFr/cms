<?php

namespace App\Service;

use App\Entity\File;
use App\Repository\FileRepository;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

/**
 * Backs the "delete unused files" admin action (step 04): a file is unused
 * when no registered FileUsageCheckerInterface reports it as referenced.
 */
class FileCleanupService
{
    /**
     * @param iterable<FileUsageCheckerInterface> $usageCheckers
     */
    public function __construct(
        private readonly FileRepository $fileRepository,
        private readonly FileUploadService $fileUploadService,
        #[AutowireIterator('app.file_usage_checker')]
        private readonly iterable $usageCheckers,
    ) {
    }

    /**
     * Lists every File not reported as in use by any registered checker.
     *
     * @return list<File>
     */
    public function findUnused(): array
    {
        $usedIds = [];
        foreach ($this->usageCheckers as $checker) {
            foreach ($checker->getUsedFileIds() as $id) {
                $usedIds[$id] = true;
            }
        }

        return array_values(array_filter(
            $this->fileRepository->findAllOrderedByCreatedAt(),
            static fn (File $file) => !isset($usedIds[$file->getId()]),
        ));
    }

    /** Deletes every unused file (on-disk assets + entity) and returns how many were removed. */
    public function removeUnused(): int
    {
        $unused = $this->findUnused();

        foreach ($unused as $file) {
            $this->fileUploadService->remove($file);
        }

        return \count($unused);
    }
}
