<?php

namespace Plugin\I18n\Service;

use Symfony\Component\Translation\Dumper\PoFileDumper;
use Symfony\Component\Translation\Loader\PoFileLoader;
use Symfony\Component\Translation\MessageCatalogue;

/**
 * Converts between the plugin's flat key/value translations and the
 * gettext PO format (step 08's export/import requirement), built on
 * symfony/translation's own PO loader/dumper rather than a new dependency.
 */
class TranslationPoConverter
{
    private readonly PoFileLoader $loader;
    private readonly PoFileDumper $dumper;

    public function __construct()
    {
        $this->loader = new PoFileLoader();
        $this->dumper = new PoFileDumper();
    }

    /**
     * Renders a set of key/value translations as a PO file's contents.
     *
     * @param array<string, string> $messages message key => translated value
     */
    public function export(string $langCode, string $domain, array $messages): string
    {
        $catalogue = new MessageCatalogue($langCode, [$domain => $messages]);

        return $this->dumper->formatCatalogue($catalogue, $domain);
    }

    /**
     * Parses a PO file into key/value translations.
     *
     * @return array<string, string> message key => translated value
     */
    public function import(string $filePath, string $langCode, string $domain): array
    {
        return $this->loader->load($filePath, $langCode, $domain)->all($domain);
    }
}
