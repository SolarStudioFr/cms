<?php

namespace App\Repository;

use App\Entity\SiteConfig;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<SiteConfig>
 */
class SiteConfigRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SiteConfig::class);
    }

    /** Singleton row, auto-created on first read (same pattern as Plugin\Homepage\Entity\HomeContent). */
    public function findOrCreate(): SiteConfig
    {
        $config = $this->createQueryBuilder('c')->getQuery()->setMaxResults(1)->getOneOrNullResult();

        if (null !== $config) {
            return $config;
        }

        $config = new SiteConfig();
        /** @var EntityManagerInterface $em */
        $em = $this->getEntityManager();
        $em->persist($config);
        $em->flush();

        return $config;
    }
}
