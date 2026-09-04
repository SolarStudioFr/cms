<?php

namespace Plugin\I18n\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Plugin\I18n\Entity\Lang;

/**
 * @extends ServiceEntityRepository<Lang>
 */
class LangRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Lang::class);
    }

    /**
     * @return list<Lang>
     */
    public function findActive(): array
    {
        return $this->createQueryBuilder('l')
            ->andWhere('l.active = true')
            ->orderBy('l.code', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
