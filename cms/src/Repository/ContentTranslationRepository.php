<?php

namespace App\Repository;

use App\Entity\ContentTranslation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ContentTranslation>
 */
class ContentTranslationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ContentTranslation::class);
    }

    /** @return list<ContentTranslation> every translated field of one content entity, for every locale (admin editor). */
    public function findByEntity(string $entityType, int $entityId): array
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.entityType = :entityType')
            ->andWhere('t.entityId = :entityId')
            ->setParameter('entityType', $entityType)
            ->setParameter('entityId', $entityId)
            ->getQuery()
            ->getResult();
    }

    /** @return array<string, string> field => translated value, for one content entity in one locale (public overlay). */
    public function findValuesForLocale(string $entityType, int $entityId, string $locale): array
    {
        $rows = $this->createQueryBuilder('t')
            ->andWhere('t.entityType = :entityType')
            ->andWhere('t.entityId = :entityId')
            ->andWhere('t.locale = :locale')
            ->setParameter('entityType', $entityType)
            ->setParameter('entityId', $entityId)
            ->setParameter('locale', $locale)
            ->getQuery()
            ->getResult();

        $values = [];
        foreach ($rows as $row) {
            $values[$row->getField()] = $row->getValue();
        }

        return $values;
    }

    public function findOneByKey(string $entityType, int $entityId, string $field, string $locale): ?ContentTranslation
    {
        return $this->findOneBy(['entityType' => $entityType, 'entityId' => $entityId, 'field' => $field, 'locale' => $locale]);
    }
}
