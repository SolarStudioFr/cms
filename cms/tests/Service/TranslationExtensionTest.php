<?php

namespace App\Tests\Service;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Plugin\I18n\Entity\Translation;
use Plugin\I18n\Repository\LangRepository;
use Plugin\I18n\Repository\TranslationRepository;
use Plugin\I18n\Twig\TranslationExtension;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Exercises the public rendering integration for step 08: the `i18n_trans`
 * Twig function falls back to the key when no translation exists, and
 * resolves the stored value once one does.
 */
class TranslationExtensionTest extends KernelTestCase
{
    protected function tearDown(): void
    {
        static::getContainer()->get(Connection::class)->executeStatement('DELETE FROM translation');

        parent::tearDown();
    }

    public function testFallsBackToTheKeyWhenNoTranslationExists(): void
    {
        self::bootKernel();
        $extension = new TranslationExtension(static::getContainer()->get(TranslationRepository::class));

        self::assertSame('unknown.key', $extension->trans('unknown.key', 'fr'));
    }

    public function testResolvesAnExistingTranslation(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        $lang = $container->get(LangRepository::class)->findOneBy(['code' => 'fr']);
        $entityManager = $container->get(EntityManagerInterface::class);

        $translation = (new Translation())->setLang($lang)->setDomain('messages')->setMessageKey('hello')->setValue('Bonjour');
        $entityManager->persist($translation);
        $entityManager->flush();

        $extension = new TranslationExtension($container->get(TranslationRepository::class));

        self::assertSame('Bonjour', $extension->trans('hello', 'fr'));
    }
}
