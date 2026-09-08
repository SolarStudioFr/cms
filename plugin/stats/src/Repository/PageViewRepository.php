<?php

namespace Plugin\Stats\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Plugin\Stats\Entity\PageView;

/**
 * @extends ServiceEntityRepository<PageView>
 *
 * Backs the admin dashboard (step 36): every method here is a GROUP BY/AVG
 * aggregate over an optional [from, to) date range, feeding one section of
 * StatsDashboardController::summary. `$from`/`$to` are always applied the
 * same way (see applyDateRange) so every metric in a summary response
 * reflects the same window.
 */
class PageViewRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PageView::class);
    }

    private function applyDateRange(\Doctrine\ORM\QueryBuilder $qb, ?\DateTimeImmutable $from, ?\DateTimeImmutable $to): void
    {
        if (null !== $from) {
            $qb->andWhere('p.createdAt >= :from')->setParameter('from', $from);
        }
        if (null !== $to) {
            $qb->andWhere('p.createdAt < :to')->setParameter('to', $to);
        }
    }

    /** @return int total number of page views in the given range */
    public function countTotal(?\DateTimeImmutable $from, ?\DateTimeImmutable $to): int
    {
        $qb = $this->createQueryBuilder('p')->select('COUNT(p.id)');
        $this->applyDateRange($qb, $from, $to);

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    /** @return float|null average time on page (seconds), ignoring views with no heartbeat yet */
    public function averageTimeOnPage(?\DateTimeImmutable $from, ?\DateTimeImmutable $to): ?float
    {
        $qb = $this->createQueryBuilder('p')
            ->select('AVG(p.timeOnPageSeconds)')
            ->andWhere('p.timeOnPageSeconds IS NOT NULL');
        $this->applyDateRange($qb, $from, $to);

        $result = $qb->getQuery()->getSingleScalarResult();

        return null === $result ? null : (float) $result;
    }

    /** @return float|null average max-scroll percentage, ignoring views with no heartbeat yet */
    public function averageScrollPercent(?\DateTimeImmutable $from, ?\DateTimeImmutable $to): ?float
    {
        $qb = $this->createQueryBuilder('p')
            ->select('AVG(p.maxScrollPercent)')
            ->andWhere('p.maxScrollPercent IS NOT NULL');
        $this->applyDateRange($qb, $from, $to);

        $result = $qb->getQuery()->getSingleScalarResult();

        return null === $result ? null : (float) $result;
    }

    /**
     * @return list<array{url: string, views: int, avgTimeOnPageSeconds: float|null}>
     */
    public function topPages(int $limit, ?\DateTimeImmutable $from, ?\DateTimeImmutable $to): array
    {
        $qb = $this->createQueryBuilder('p')
            ->select('p.url AS url', 'COUNT(p.id) AS views', 'AVG(p.timeOnPageSeconds) AS avgTimeOnPageSeconds')
            ->groupBy('p.url')
            ->orderBy('views', 'DESC')
            ->setMaxResults($limit);
        $this->applyDateRange($qb, $from, $to);

        return array_map(
            static fn (array $row) => [
                'url' => $row['url'],
                'views' => (int) $row['views'],
                'avgTimeOnPageSeconds' => null === $row['avgTimeOnPageSeconds'] ? null : (float) $row['avgTimeOnPageSeconds'],
            ],
            $qb->getQuery()->getResult(),
        );
    }

    /**
     * Generic count-by-field breakdown, used for browser/OS/language/country/device
     * type. `$field` must be one of PageView's own scalar property names -
     * never take it from user input.
     *
     * @return list<array{value: string|null, count: int}>
     */
    public function breakdown(string $field, ?\DateTimeImmutable $from, ?\DateTimeImmutable $to): array
    {
        $qb = $this->createQueryBuilder('p')
            ->select(sprintf('p.%s AS value', $field), 'COUNT(p.id) AS count')
            ->groupBy(sprintf('p.%s', $field))
            ->orderBy('count', 'DESC');
        $this->applyDateRange($qb, $from, $to);

        return array_map(
            static fn (array $row) => ['value' => $row['value'], 'count' => (int) $row['count']],
            $qb->getQuery()->getResult(),
        );
    }

    /** @return array{bot: int, human: int} */
    public function botHumanCounts(?\DateTimeImmutable $from, ?\DateTimeImmutable $to): array
    {
        $qb = $this->createQueryBuilder('p')
            ->select('p.isBot AS isBot', 'COUNT(p.id) AS count')
            ->groupBy('p.isBot');
        $this->applyDateRange($qb, $from, $to);

        $counts = ['bot' => 0, 'human' => 0];
        foreach ($qb->getQuery()->getResult() as $row) {
            $counts[$row['isBot'] ? 'bot' : 'human'] = (int) $row['count'];
        }

        return $counts;
    }

    /** @return list<array{name: string, count: int}> */
    public function topBots(int $limit, ?\DateTimeImmutable $from, ?\DateTimeImmutable $to): array
    {
        $qb = $this->createQueryBuilder('p')
            ->select('p.botName AS name', 'COUNT(p.id) AS count')
            ->andWhere('p.isBot = true')
            ->andWhere('p.botName IS NOT NULL')
            ->groupBy('p.botName')
            ->orderBy('count', 'DESC')
            ->setMaxResults($limit);
        $this->applyDateRange($qb, $from, $to);

        return array_map(
            static fn (array $row) => ['name' => $row['name'], 'count' => (int) $row['count']],
            $qb->getQuery()->getResult(),
        );
    }
}
