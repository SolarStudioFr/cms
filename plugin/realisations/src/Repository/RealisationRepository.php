<?php

namespace Plugin\Realisations\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Plugin\Realisations\Entity\Realisation;
use Plugin\Realisations\Entity\RealisationStatus;

/**
 * @extends ServiceEntityRepository<Realisation>
 */
class RealisationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Realisation::class);
    }

    /**
     * @return list<Realisation>
     */
    public function findPublished(): array
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.status = :status')
            ->setParameter('status', RealisationStatus::Published)
            ->orderBy('r.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findOnePublishedById(int $id): ?Realisation
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.status = :status')
            ->andWhere('r.id = :id')
            ->setParameter('status', RealisationStatus::Published)
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
