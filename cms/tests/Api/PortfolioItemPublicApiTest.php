<?php

namespace App\Tests\Api;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Plugin\Portfolio\Entity\PortfolioItem;
use Plugin\Portfolio\Entity\PortfolioItemStatus;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class PortfolioItemPublicApiTest extends WebTestCase
{
    protected function tearDown(): void
    {
        static::getContainer()->get(Connection::class)->executeStatement('DELETE FROM portfolio_item');

        parent::tearDown();
    }

    public function testOnlyPublishedItemsAreListedPublicly(): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get(EntityManagerInterface::class);

        $draft = (new PortfolioItem())->setTitle('Draft item')->setContent('Draft content');
        $published = (new PortfolioItem())->setTitle('Published item')->setContent('Published content')
            ->setStatus(PortfolioItemStatus::Published)
            ->setCoverImageUrl('/upload/img/webp/cover.webp')
            ->setCoverImageAlt('Cover');
        $archived = (new PortfolioItem())->setTitle('Archived item')->setContent('Archived content')
            ->setStatus(PortfolioItemStatus::Archived);

        foreach ([$draft, $published, $archived] as $item) {
            $em->persist($item);
        }
        $em->flush();

        $client->request('GET', '/api/portfolio');

        self::assertResponseIsSuccessful();
        $list = json_decode($client->getResponse()->getContent(), true);
        self::assertCount(1, $list);
        self::assertSame('Published item', $list[0]['title']);
        self::assertSame('/upload/img/webp/cover.webp', $list[0]['coverImageUrl']);
    }

    public function testDraftItemIsNotReachableViaPublicItemEndpoint(): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get(EntityManagerInterface::class);

        $draft = (new PortfolioItem())->setTitle('Draft item')->setContent('Draft content');
        $em->persist($draft);
        $em->flush();

        $client->request('GET', "/api/portfolio/{$draft->getId()}");

        self::assertResponseStatusCodeSame(404);
    }

    public function testPublishedItemIsReachableViaPublicItemEndpoint(): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get(EntityManagerInterface::class);

        $published = (new PortfolioItem())->setTitle('Published item')->setContent('Published content')
            ->setStatus(PortfolioItemStatus::Published);
        $em->persist($published);
        $em->flush();

        $client->request('GET', "/api/portfolio/{$published->getId()}");

        self::assertResponseIsSuccessful();
        self::assertSame('Published item', json_decode($client->getResponse()->getContent(), true)['title']);
    }
}
