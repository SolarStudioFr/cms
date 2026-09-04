<?php

namespace App\Tests\Api;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Plugin\Realisations\Entity\Realisation;
use Plugin\Realisations\Entity\RealisationStatus;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class RealisationPublicApiTest extends WebTestCase
{
    protected function tearDown(): void
    {
        static::getContainer()->get(Connection::class)->executeStatement('DELETE FROM realisation');

        parent::tearDown();
    }

    public function testOnlyPublishedRealisationsAreListedPublicly(): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get(EntityManagerInterface::class);

        $draft = (new Realisation())->setTitle('Draft realisation')->setContent('Draft content');
        $published = (new Realisation())->setTitle('Published realisation')->setContent('Published content')
            ->setStatus(RealisationStatus::Published)
            ->setCoverImageUrl('/upload/img/webp/cover.webp')
            ->setCoverImageAlt('Cover');
        $archived = (new Realisation())->setTitle('Archived realisation')->setContent('Archived content')
            ->setStatus(RealisationStatus::Archived);

        foreach ([$draft, $published, $archived] as $realisation) {
            $em->persist($realisation);
        }
        $em->flush();

        $client->request('GET', '/api/realisations');

        self::assertResponseIsSuccessful();
        $list = json_decode($client->getResponse()->getContent(), true);
        self::assertCount(1, $list);
        self::assertSame('Published realisation', $list[0]['title']);
        self::assertSame('/upload/img/webp/cover.webp', $list[0]['coverImageUrl']);
    }

    public function testDraftRealisationIsNotReachableViaPublicItemEndpoint(): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get(EntityManagerInterface::class);

        $draft = (new Realisation())->setTitle('Draft realisation')->setContent('Draft content');
        $em->persist($draft);
        $em->flush();

        $client->request('GET', "/api/realisations/{$draft->getId()}");

        self::assertResponseStatusCodeSame(404);
    }

    public function testPublishedRealisationIsReachableViaPublicItemEndpoint(): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get(EntityManagerInterface::class);

        $published = (new Realisation())->setTitle('Published realisation')->setContent('Published content')
            ->setStatus(RealisationStatus::Published);
        $em->persist($published);
        $em->flush();

        $client->request('GET', "/api/realisations/{$published->getId()}");

        self::assertResponseIsSuccessful();
        self::assertSame('Published realisation', json_decode($client->getResponse()->getContent(), true)['title']);
    }
}
