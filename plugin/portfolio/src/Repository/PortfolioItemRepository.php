<?php

namespace Plugin\Portfolio\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Plugin\Portfolio\Entity\PortfolioItem;
use Plugin\Portfolio\Entity\PortfolioItemStatus;

/**
 * @extends ServiceEntityRepository<PortfolioItem>
 */
class PortfolioItemRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PortfolioItem::class);
    }

    /**
     * @return list<PortfolioItem>
     */
    public function findPublished(): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.status = :status')
            ->setParameter('status', PortfolioItemStatus::Published)
            ->orderBy('p.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findOnePublishedById(int $id): ?PortfolioItem
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.status = :status')
            ->andWhere('p.id = :id')
            ->setParameter('status', PortfolioItemStatus::Published)
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
