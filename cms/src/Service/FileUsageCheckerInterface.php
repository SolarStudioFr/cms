<?php

namespace App\Service;

use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

/**
 * Extension point for FileCleanupService (step 04): any plugin/feature that
 * references a File entity (builder modules, actualités, réalisations,
 * accueil, site config logo/favicon, ...) implements this and gets
 * auto-registered - "unused" file detection stays correct as new file
 * consumers are added, without changing FileCleanupService itself. No
 * implementation exists yet in this codebase (none of those features are
 * built), so every stored file currently counts as unused.
 */
#[AutoconfigureTag('app.file_usage_checker')]
interface FileUsageCheckerInterface
{
    /**
     * Ids of every App\Entity\File this checker considers referenced/in use.
     *
     * @return iterable<int>
     */
    public function getUsedFileIds(): iterable;
}
