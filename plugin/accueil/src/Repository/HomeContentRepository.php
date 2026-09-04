<?php

namespace Plugin\Accueil\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Plugin\Accueil\Entity\HomeContent;

/**
 * @extends ServiceEntityRepository<HomeContent>
 */
class HomeContentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, HomeContent::class);
    }

    /** The single row, if it has been created yet - see HomeContentProvider for the auto-create-on-first-read logic. */
    public function findSingleton(): ?HomeContent
    {
        return $this->createQueryBuilder('h')
            ->orderBy('h.id', 'ASC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
