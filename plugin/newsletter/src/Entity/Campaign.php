<?php

namespace Plugin\Newsletter\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Plugin\Newsletter\Repository\CampaignRepository;
use Symfony\Component\Serializer\Attribute\Groups;

/**
 * A newsletter campaign (step 23): subject + HTML body edited with the
 * shared RichTextEditor (no page-builder integration here - email HTML
 * needs to stay simple/inlineable, and the builder's modules, e.g. the
 * slider, aren't email-safe markup). `totalRecipients` is a snapshot taken
 * when sending starts (not a live subscriber count) so the progress bar
 * (step 24) has a stable denominator even if someone subscribes mid-send.
 */
#[ORM\Entity(repositoryClass: CampaignRepository::class)]
#[ORM\Table(name: 'newsletter_campaign')]
#[ApiResource(
    operations: [
        new GetCollection(
            uriTemplate: '/admin/newsletter/campaigns',
            security: "is_granted('ROLE_SUPER_ADMIN')",
        ),
        new Get(
            uriTemplate: '/admin/newsletter/campaigns/{id}',
            security: "is_granted('ROLE_SUPER_ADMIN')",
        ),
        new Post(
            uriTemplate: '/admin/newsletter/campaigns',
            security: "is_granted('ROLE_SUPER_ADMIN')",
        ),
        new Patch(
            uriTemplate: '/admin/newsletter/campaigns/{id}',
            security: "is_granted('ROLE_SUPER_ADMIN')",
        ),
        new Delete(
            uriTemplate: '/admin/newsletter/campaigns/{id}',
            security: "is_granted('ROLE_SUPER_ADMIN')",
        ),
    ],
    normalizationContext: ['groups' => ['campaign:read']],
    denormalizationContext: ['groups' => ['campaign:write']],
)]
class Campaign
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['campaign:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['campaign:read', 'campaign:write'])]
    private string $subject = '';

    #[ORM\Column(type: Types::TEXT)]
    #[Groups(['campaign:read', 'campaign:write'])]
    private string $content = '';

    #[ORM\Column(length: 20, enumType: CampaignStatus::class)]
    #[Groups(['campaign:read'])]
    private CampaignStatus $status = CampaignStatus::Draft;

    #[ORM\Column]
    #[Groups(['campaign:read'])]
    private int $totalRecipients = 0;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    #[Groups(['campaign:read'])]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    #[Groups(['campaign:read'])]
    private ?\DateTimeImmutable $sentAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSubject(): string
    {
        return $this->subject;
    }

    public function setSubject(string $subject): static
    {
        $this->subject = $subject;

        return $this;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function setContent(string $content): static
    {
        $this->content = $content;

        return $this;
    }

    public function getStatus(): CampaignStatus
    {
        return $this->status;
    }

    public function setStatus(CampaignStatus $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getTotalRecipients(): int
    {
        return $this->totalRecipients;
    }

    public function setTotalRecipients(int $totalRecipients): static
    {
        $this->totalRecipients = $totalRecipients;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getSentAt(): ?\DateTimeImmutable
    {
        return $this->sentAt;
    }

    public function setSentAt(?\DateTimeImmutable $sentAt): static
    {
        $this->sentAt = $sentAt;

        return $this;
    }
}
