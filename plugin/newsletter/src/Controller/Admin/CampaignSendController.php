<?php

namespace Plugin\Newsletter\Controller\Admin;

use App\Service\MailService;
use Doctrine\ORM\EntityManagerInterface;
use Plugin\Newsletter\Entity\CampaignSend;
use Plugin\Newsletter\Entity\CampaignStatus;
use Plugin\Newsletter\Repository\CampaignRepository;
use Plugin\Newsletter\Repository\CampaignSendRepository;
use Plugin\Newsletter\Repository\SubscriberRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Backend for the browser-driven bulk send (step 24): one call = one email.
 * The admin page calls send-next repeatedly until `done` is true, updating
 * a progress bar from `sentCount`/`total` between calls - never a single
 * blocking request that mails everyone. Already gated by the existing
 * ^/api/admin ROLE_SUPER_ADMIN access_control rule.
 */
class CampaignSendController
{
    public function __construct(
        private readonly CampaignRepository $campaignRepository,
        private readonly SubscriberRepository $subscriberRepository,
        private readonly CampaignSendRepository $campaignSendRepository,
        private readonly MailService $mailService,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    #[Route('/api/admin/newsletter/campaigns/{id}/send-next', name: 'admin_newsletter_campaign_send_next', methods: ['POST'])]
    public function __invoke(int $id): JsonResponse
    {
        $campaign = $this->campaignRepository->find($id);
        if (null === $campaign) {
            return new JsonResponse(['error' => 'Campaign not found.'], Response::HTTP_NOT_FOUND);
        }

        if (CampaignStatus::Sent === $campaign->getStatus()) {
            return new JsonResponse([
                'sentCount' => $campaign->getTotalRecipients(),
                'total' => $campaign->getTotalRecipients(),
                'done' => true,
            ]);
        }

        // First call for this campaign: snapshot the recipient count so the
        // progress bar's denominator doesn't move under it if someone
        // subscribes while a send is in progress.
        if (CampaignStatus::Draft === $campaign->getStatus()) {
            $campaign->setStatus(CampaignStatus::Sending);
            $campaign->setTotalRecipients(\count($this->subscriberRepository->findAll()));
            $this->entityManager->flush();
        }

        $next = $this->campaignSendRepository->findNextPendingSubscriber($campaign);

        if (null === $next) {
            $campaign->setStatus(CampaignStatus::Sent);
            $campaign->setSentAt(new \DateTimeImmutable());
            $this->entityManager->flush();

            return new JsonResponse([
                'sentCount' => $campaign->getTotalRecipients(),
                'total' => $campaign->getTotalRecipients(),
                'done' => true,
            ]);
        }

        $this->mailService->send($next->getEmail(), $campaign->getSubject(), $campaign->getContent());
        $this->entityManager->persist(new CampaignSend($campaign, $next));
        $this->entityManager->flush();

        return new JsonResponse([
            'sentCount' => $this->campaignSendRepository->countSent($campaign),
            'total' => $campaign->getTotalRecipients(),
            'done' => false,
        ]);
    }
}
