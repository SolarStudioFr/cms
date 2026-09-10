<?php

namespace App\Entity;

use App\Repository\ContentTranslationRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * One translated field value for one content entity (step 58): the
 * *content* language mechanism, distinct from the *interface* language
 * catalogs (steps 53-57). Generic overlay rather than a table per content
 * type - `entityType` is a short plugin-chosen string (e.g. "page",
 * "portfolio_item", "news_article", "homepage"), `entityId` its row id,
 * `field` one of that content type's own translatable field names (e.g.
 * "title", "content"). This keeps i18n itself free of any knowledge of
 * Page/Portfolio/News/Homepage - each content type's own public provider
 * calls App\Service\ContentTranslator with its own field list, i18n never
 * imports or references any plugin.
 */
#[ORM\Entity(repositoryClass: ContentTranslationRepository::class)]
#[ORM\Table(name: 'content_translation')]
#[ORM\UniqueConstraint(name: 'uniq_content_translation_key', columns: ['entity_type', 'entity_id', 'field', 'locale'])]
class ContentTranslation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    private string $entityType = '';

    #[ORM\Column]
    private int $entityId = 0;

    #[ORM\Column(length: 50)]
    private string $field = '';

    #[ORM\Column(length: 10)]
    private string $locale = '';

    #[ORM\Column(type: Types::TEXT)]
    private string $value = '';

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEntityType(): string
    {
        return $this->entityType;
    }

    public function setEntityType(string $entityType): static
    {
        $this->entityType = $entityType;

        return $this;
    }

    public function getEntityId(): int
    {
        return $this->entityId;
    }

    public function setEntityId(int $entityId): static
    {
        $this->entityId = $entityId;

        return $this;
    }

    public function getField(): string
    {
        return $this->field;
    }

    public function setField(string $field): static
    {
        $this->field = $field;

        return $this;
    }

    public function getLocale(): string
    {
        return $this->locale;
    }

    public function setLocale(string $locale): static
    {
        $this->locale = $locale;

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
