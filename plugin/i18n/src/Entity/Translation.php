<?php

namespace Plugin\I18n\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Plugin\I18n\Repository\TranslationRepository;

/**
 * One translated string (step 08): a (lang, domain, key) triple mapping to
 * its translated value. "Domain" groups strings the way gettext PO domains
 * do (e.g. "messages" for interface strings, or a plugin-specific domain
 * for its own content) - both interface and content translations share
 * this same generic store, distinguished only by domain.
 */
#[ORM\Entity(repositoryClass: TranslationRepository::class)]
#[ORM\Table(name: 'translation')]
#[ORM\UniqueConstraint(name: 'uniq_translation_lang_domain_key', columns: ['lang_id', 'domain', 'message_key'])]
class Translation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Lang::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Lang $lang;

    #[ORM\Column(length: 100)]
    private string $domain = 'messages';

    /** The gettext msgid - named to avoid the SQL reserved word "key". */
    #[ORM\Column(length: 255)]
    private string $messageKey = '';

    #[ORM\Column(type: Types::TEXT)]
    private string $value = '';

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLang(): Lang
    {
        return $this->lang;
    }

    public function setLang(Lang $lang): static
    {
        $this->lang = $lang;

        return $this;
    }

    public function getDomain(): string
    {
        return $this->domain;
    }

    public function setDomain(string $domain): static
    {
        $this->domain = $domain;

        return $this;
    }

    public function getMessageKey(): string
    {
        return $this->messageKey;
    }

    public function setMessageKey(string $messageKey): static
    {
        $this->messageKey = $messageKey;

        return $this;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function setValue(string $value): static
    {
        $this->value = $value;

        return $this;
    }
}
