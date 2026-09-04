<?php

namespace App\Repository;

use App\Entity\Menu;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Menu>
 */
class MenuRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Menu::class);
    }

    /** @return list<Menu> menus actually attached to a template hook (see ThemeRegistry) */
    public function findAllAssignedToHook(): array
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.hookName IS NOT NULL')
            ->orderBy('m.name', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
