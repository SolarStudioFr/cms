<?php

namespace Plugin\I18n\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Plugin\I18n\Entity\Lang;
use Plugin\I18n\Entity\Translation;

/**
 * @extends ServiceEntityRepository<Translation>
 */
class TranslationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Translation::class);
    }

    /**
     * @return list<Translation>
     */
    public function findByLangAndDomain(Lang $lang, string $domain): array
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.lang = :lang')
            ->andWhere('t.domain = :domain')
            ->setParameter('lang', $lang)
            ->setParameter('domain', $domain)
            ->orderBy('t.messageKey', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findOneByLangDomainKey(Lang $lang, string $domain, string $messageKey): ?Translation
    {
        return $this->findOneBy(['lang' => $lang, 'domain' => $domain, 'messageKey' => $messageKey]);
    }

    /**
     * Used by the public Twig integration: looks a translation up by locale
     * code directly, without the caller having to load a Lang first.
     */
    public function findValue(string $langCode, string $domain, string $messageKey): ?string
    {
        $translation = $this->createQueryBuilder('t')
            ->join('t.lang', 'l')
            ->andWhere('l.code = :code')
            ->andWhere('t.domain = :domain')
            ->andWhere('t.messageKey = :key')
            ->setParameter('code', $langCode)
            ->setParameter('domain', $domain)
            ->setParameter('key', $messageKey)
            ->getQuery()
            ->getOneOrNullResult();

        return $translation?->getValue();
    }
}
