<?php

namespace App\Tests\Api;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Plugin\Actualites\Entity\Actualite;
use Plugin\Actualites\Entity\ActualiteStatus;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ActualitePublicApiTest extends WebTestCase
{
    protected function tearDown(): void
    {
        static::getContainer()->get(Connection::class)->executeStatement('DELETE FROM actualite');

        parent::tearDown();
    }

    public function testOnlyPublishedActualitesAreListedPublicly(): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get(EntityManagerInterface::class);

        $draft = (new Actualite())->setTitle('Draft actualite')->setContent('Draft content');
        $published = (new Actualite())->setTitle('Published actualite')->setContent('Published content')
            ->setStatus(ActualiteStatus::Published)
            ->setCoverImageUrl('/upload/img/webp/cover.webp')
            ->setCoverImageAlt('Cover');
        $archived = (new Actualite())->setTitle('Archived actualite')->setContent('Archived content')
            ->setStatus(ActualiteStatus::Archived);

        foreach ([$draft, $published, $archived] as $actualite) {
            $em->persist($actualite);
        }
        $em->flush();

        $client->request('GET', '/api/actualites');

        self::assertResponseIsSuccessful();
        $list = json_decode($client->getResponse()->getContent(), true);
        self::assertCount(1, $list);
        self::assertSame('Published actualite', $list[0]['title']);
        self::assertSame('/upload/img/webp/cover.webp', $list[0]['coverImageUrl']);
    }

    public function testDraftActualiteIsNotReachableViaPublicItemEndpoint(): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get(EntityManagerInterface::class);

        $draft = (new Actualite())->setTitle('Draft actualite')->setContent('Draft content');
        $em->persist($draft);
        $em->flush();

        $client->request('GET', "/api/actualites/{$draft->getId()}");

        self::assertResponseStatusCodeSame(404);
    }

    public function testPublishedActualiteIsReachableViaPublicItemEndpoint(): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get(EntityManagerInterface::class);

        $published = (new Actualite())->setTitle('Published actualite')->setContent('Published content')
            ->setStatus(ActualiteStatus::Published);
        $em->persist($published);
        $em->flush();

        $client->request('GET', "/api/actualites/{$published->getId()}");

        self::assertResponseIsSuccessful();
        self::assertSame('Published actualite', json_decode($client->getResponse()->getContent(), true)['title']);
    }
}
