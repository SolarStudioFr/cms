<?php

namespace Plugin\Newsletter\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use Doctrine\ORM\Mapping as ORM;
use Plugin\Newsletter\Repository\SubscriberRepository;
use Plugin\Newsletter\State\SubscriberSignupProcessor;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * A newsletter subscriber (step 23's admin list, step 25's public signup).
 * Just an email address and when it was captured, no double opt-in/
 * confirmation flow (not asked for by the roadmap). `name` and `locale`
 * (step 58 follow-up) are captured at signup to prepare a future
 * per-subscriber-language send - not built yet, this only records the data.
 */
#[ORM\Entity(repositoryClass: SubscriberRepository::class)]
#[ORM\Table(name: 'newsletter_subscriber')]
#[ORM\UniqueConstraint(name: 'uniq_newsletter_subscriber_email', columns: ['email'])]
#[ApiResource(
    operations: [
        // Admin: list + remove (step 23).
        new GetCollection(
            uriTemplate: '/admin/newsletter/subscribers',
            security: "is_granted('ROLE_SUPER_ADMIN')",
        ),
        new Delete(
            uriTemplate: '/admin/newsletter/subscribers/{id}',
            security: "is_granted('ROLE_SUPER_ADMIN')",
        ),
        // Public: signup form (step 25) - idempotent on an already
        // subscribed email, see SubscriberSignupProcessor.
        new Post(
            uriTemplate: '/newsletter/subscribers',
            security: "is_granted('PUBLIC_ACCESS')",
            processor: SubscriberSignupProcessor::class,
            validationContext: ['groups' => ['Default']],
        ),
    ],
    normalizationContext: ['groups' => ['subscriber:read']],
    denormalizationContext: ['groups' => ['subscriber:write']],
)]
class Subscriber
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['subscriber:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['subscriber:read', 'subscriber:write'])]
    #[Assert\NotBlank]
    #[Assert\Email]
    private string $email = '';

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['subscriber:read', 'subscriber:write'])]
    private ?string $name = null;

    /** ISO locale code the subscriber's browser was set to at signup (e.g. "fr"), not user-entered - see NewsletterSignup.jsx. */
    #[ORM\Column(length: 10, nullable: true)]
    #[Groups(['subscriber:read', 'subscriber:write'])]
    private ?string $locale = null;

    #[ORM\Column(type: 'datetime_immutable')]
    #[Groups(['subscriber:read'])]
    private \DateTimeImmutable $subscribedAt;

    public function __construct()
    {
        $this->subscribedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getLocale(): ?string
    {
        return $this->locale;
    }

    public function setLocale(?string $locale): static
    {
        $this->locale = $locale;

        return $this;
    }

    public function getSubscribedAt(): \DateTimeImmutable
    {
        return $this->subscribedAt;
    }
}
