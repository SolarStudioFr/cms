<?php

namespace App\Repository;

use App\Entity\AdminMenuConfig;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AdminMenuConfig>
 */
class AdminMenuConfigRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AdminMenuConfig::class);
    }

    /** Singleton row, auto-created empty on first read (same pattern as SiteConfig). */
    public function findOrCreate(): AdminMenuConfig
    {
        $config = $this->createQueryBuilder('c')->getQuery()->setMaxResults(1)->getOneOrNullResult();

        if (null !== $config) {
            return $config;
        }

        $config = new AdminMenuConfig();
        /** @var EntityManagerInterface $em */
        $em = $this->getEntityManager();
        $em->persist($config);
        $em->flush();

        return $config;
    }
}
