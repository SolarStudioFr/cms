<?php

namespace Plugin\Newsletter\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Plugin\Newsletter\Entity\Campaign;
use Plugin\Newsletter\Entity\CampaignSend;
use Plugin\Newsletter\Entity\Subscriber;

/**
 * @extends ServiceEntityRepository<CampaignSend>
 */
class CampaignSendRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CampaignSend::class);
    }

    /**
     * The next subscriber for this campaign that has no CampaignSend row
     * yet (a plain NOT IN would need to load every already-sent id first;
     * this left-join form lets the database do that filtering).
     */
    public function findNextPendingSubscriber(Campaign $campaign): ?Subscriber
    {
        $result = $this->getEntityManager()->createQueryBuilder()
            ->select('s')
            ->from(Subscriber::class, 's')
            ->leftJoin(
                CampaignSend::class,
                'cs',
                'WITH',
                'cs.subscriber = s AND cs.campaign = :campaign',
            )
            ->andWhere('cs.id IS NULL')
            ->setParameter('campaign', $campaign)
            ->orderBy('s.id', 'ASC')
            ->setMaxResults(1)
            ->getQuery()
            ->getResult();

        return $result[0] ?? null;
    }

    public function countSent(Campaign $campaign): int
    {
        return (int) $this->createQueryBuilder('cs')
            ->select('COUNT(cs.id)')
            ->andWhere('cs.campaign = :campaign')
            ->setParameter('campaign', $campaign)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
