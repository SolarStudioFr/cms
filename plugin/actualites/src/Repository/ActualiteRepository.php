<?php

namespace Plugin\Actualites\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Plugin\Actualites\Entity\Actualite;
use Plugin\Actualites\Entity\ActualiteStatus;

/**
 * @extends ServiceEntityRepository<Actualite>
 */
class ActualiteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Actualite::class);
    }

    /**
     * @return list<Actualite>
     */
    public function findPublished(): array
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.status = :status')
            ->setParameter('status', ActualiteStatus::Published)
            ->orderBy('a.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findOnePublishedById(int $id): ?Actualite
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.status = :status')
            ->andWhere('a.id = :id')
            ->setParameter('status', ActualiteStatus::Published)
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
