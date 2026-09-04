<?php

namespace App\Repository;

use App\Entity\File;
use App\Entity\FileType;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<File>
 */
class FileRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, File::class);
    }

    /**
     * Lists stored files, most recent first, for the admin file manager and
     * the media picker (step 03) - optionally restricted to a set of types
     * so the picker can be configured per caller (e.g. images only).
     *
     * @param list<FileType>|null $types
     *
     * @return list<File>
     */
    public function findAllOrderedByCreatedAt(?array $types = null): array
    {
        $qb = $this->createQueryBuilder('f')
            ->orderBy('f.createdAt', 'DESC');

        if (null !== $types && [] !== $types) {
            $qb->andWhere('f.type IN (:types)')->setParameter('types', $types);
        }

        return $qb->getQuery()->getResult();
    }
}
