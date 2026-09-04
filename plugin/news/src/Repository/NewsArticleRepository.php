<?php

namespace Plugin\News\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Plugin\News\Entity\NewsArticle;
use Plugin\News\Entity\NewsArticleStatus;

/**
 * @extends ServiceEntityRepository<NewsArticle>
 */
class NewsArticleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, NewsArticle::class);
    }

    /**
     * @return list<NewsArticle>
     */
    public function findPublished(): array
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.status = :status')
            ->setParameter('status', NewsArticleStatus::Published)
            ->orderBy('a.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findOnePublishedById(int $id): ?NewsArticle
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.status = :status')
            ->andWhere('a.id = :id')
            ->setParameter('status', NewsArticleStatus::Published)
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
