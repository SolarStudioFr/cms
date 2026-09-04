<?php

namespace Plugin\I18n\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use Doctrine\ORM\Mapping as ORM;
use Plugin\I18n\Repository\LangRepository;
use Plugin\I18n\State\ActiveLangCollectionProvider;
use Symfony\Component\Serializer\Attribute\Groups;

/**
 * A site language (step 07): its ISO code and display label, and whether
 * it's currently active. Backs the i18n plugin's admin language manager
 * and, once active, is what translation management (step 08) and any
 * multilingual-aware plugin builds on.
 */
#[ORM\Entity(repositoryClass: LangRepository::class)]
#[ORM\Table(name: 'lang')]
#[ORM\UniqueConstraint(name: 'uniq_lang_code', columns: ['code'])]
#[ApiResource(
    operations: [
        // Admin: full CRUD over every language, active or not.
        new GetCollection(
            uriTemplate: '/admin/langs',
            security: "is_granted('ROLE_SUPER_ADMIN')",
        ),
        new Get(
            uriTemplate: '/admin/langs/{id}',
            security: "is_granted('ROLE_SUPER_ADMIN')",
        ),
        new Post(
            uriTemplate: '/admin/langs',
            security: "is_granted('ROLE_SUPER_ADMIN')",
        ),
        new Patch(
            uriTemplate: '/admin/langs/{id}',
            security: "is_granted('ROLE_SUPER_ADMIN')",
        ),
        new Delete(
            uriTemplate: '/admin/langs/{id}',
            security: "is_granted('ROLE_SUPER_ADMIN')",
        ),
        // Public: active languages only, read-only (language switcher, step 08).
        new GetCollection(
            uriTemplate: '/langs',
            security: "is_granted('PUBLIC_ACCESS')",
            provider: ActiveLangCollectionProvider::class,
            paginationEnabled: false,
        ),
    ],
    normalizationContext: ['groups' => ['lang:read']],
    denormalizationContext: ['groups' => ['lang:write']],
)]
class Lang
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['lang:read'])]
    private ?int $id = null;

    /** ISO locale code, e.g. "fr" or "en". */
    #[ORM\Column(length: 10)]
    #[Groups(['lang:read', 'lang:write'])]
    private string $code = '';

    /** Display name shown in the admin and any public language switcher, e.g. "Français". */
    #[ORM\Column(length: 100)]
    #[Groups(['lang:read', 'lang:write'])]
    private string $label = '';

    #[ORM\Column]
    #[Groups(['lang:read', 'lang:write'])]
    private bool $active = true;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function setCode(string $code): static
    {
        $this->code = $code;

        return $this;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function setLabel(string $label): static
    {
        $this->label = $label;

        return $this;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function setActive(bool $active): static
    {
        $this->active = $active;

        return $this;
    }
}
