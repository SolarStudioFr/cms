<?php

namespace Plugin\Stats\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Plugin\Stats\Entity\ClickEvent;

/**
 * @extends ServiceEntityRepository<ClickEvent>
 */
class ClickEventRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ClickEvent::class);
    }

    /**
     * Most-clicked elements (by type + label), for the admin dashboard.
     *
     * @return list<array{elementType: string, label: string|null, targetUrl: string|null, count: int}>
     */
    public function topClicks(int $limit, ?\DateTimeImmutable $from, ?\DateTimeImmutable $to): array
    {
        $qb = $this->createQueryBuilder('c')
            ->select('c.elementType AS elementType', 'c.label AS label', 'c.targetUrl AS targetUrl', 'COUNT(c.id) AS count')
            ->groupBy('c.elementType', 'c.label', 'c.targetUrl')
            ->orderBy('count', 'DESC')
            ->setMaxResults($limit);

        if (null !== $from) {
            $qb->andWhere('c.createdAt >= :from')->setParameter('from', $from);
        }
        if (null !== $to) {
            $qb->andWhere('c.createdAt < :to')->setParameter('to', $to);
        }

        return array_map(
            static fn (array $row) => [
                'elementType' => $row['elementType'],
                'label' => $row['label'],
                'targetUrl' => $row['targetUrl'],
                'count' => (int) $row['count'],
            ],
            $qb->getQuery()->getResult(),
        );
    }
}
