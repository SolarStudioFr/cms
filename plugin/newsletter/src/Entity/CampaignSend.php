<?php

namespace Plugin\Newsletter\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Plugin\Newsletter\Repository\CampaignSendRepository;

/**
 * Internal bookkeeping row (one per campaign+subscriber pair actually
 * mailed) - not an ApiResource, only ever read/written by
 * CampaignSendController. Lets the browser-driven bulk send (step 24) be
 * safely resumed after an interruption: send-next always skips subscribers
 * that already have a row here instead of re-sending them.
 */
#[ORM\Entity(repositoryClass: CampaignSendRepository::class)]
#[ORM\Table(name: 'newsletter_campaign_send')]
#[ORM\UniqueConstraint(name: 'uniq_campaign_send_pair', columns: ['campaign_id', 'subscriber_id'])]
class CampaignSend
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Campaign::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Campaign $campaign;

    #[ORM\ManyToOne(targetEntity: Subscriber::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Subscriber $subscriber;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $sentAt;

    public function __construct(Campaign $campaign, Subscriber $subscriber)
    {
        $this->campaign = $campaign;
        $this->subscriber = $subscriber;
        $this->sentAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCampaign(): Campaign
    {
        return $this->campaign;
    }

    public function getSubscriber(): Subscriber
    {
        return $this->subscriber;
    }

    public function getSentAt(): \DateTimeImmutable
    {
        return $this->sentAt;
    }
}
