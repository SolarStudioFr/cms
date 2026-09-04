<?php

namespace Plugin\I18n\Twig;

use Plugin\I18n\Repository\TranslationRepository;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Public rendering integration for step 08: exposes an `i18n_trans()` Twig
 * function so any theme template can pull translated content out of the
 * translation store. Auto-registered by TwigBundle's extension detection
 * (services.yaml already autoconfigures every Plugin\I18n\ service).
 */
class TranslationExtension extends AbstractExtension
{
    public function __construct(
        private readonly TranslationRepository $translationRepository,
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('i18n_trans', $this->trans(...)),
        ];
    }

    /**
     * Looks up a translated string; falls back to the key itself if no
     * translation exists for that lang/domain/key.
     */
    public function trans(string $key, string $lang, string $domain = 'messages'): string
    {
        return $this->translationRepository->findValue($lang, $domain, $key) ?? $key;
    }
}
