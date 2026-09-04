<?php

namespace App\Tests\Api;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Plugin\News\Entity\NewsArticle;
use Plugin\News\Entity\NewsArticleStatus;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class NewsArticlePublicApiTest extends WebTestCase
{
    protected function tearDown(): void
    {
        static::getContainer()->get(Connection::class)->executeStatement('DELETE FROM news_article');

        parent::tearDown();
    }

    public function testOnlyPublishedArticlesAreListedPublicly(): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get(EntityManagerInterface::class);

        $draft = (new NewsArticle())->setTitle('Draft actualite')->setContent('Draft content');
        $published = (new NewsArticle())->setTitle('Published actualite')->setContent('Published content')
            ->setStatus(NewsArticleStatus::Published)
            ->setCoverImageUrl('/upload/img/webp/cover.webp')
            ->setCoverImageAlt('Cover');
        $archived = (new NewsArticle())->setTitle('Archived actualite')->setContent('Archived content')
            ->setStatus(NewsArticleStatus::Archived);

        foreach ([$draft, $published, $archived] as $article) {
            $em->persist($article);
        }
        $em->flush();

        $client->request('GET', '/api/news');

        self::assertResponseIsSuccessful();
        $list = json_decode($client->getResponse()->getContent(), true);
        self::assertCount(1, $list);
        self::assertSame('Published actualite', $list[0]['title']);
        self::assertSame('/upload/img/webp/cover.webp', $list[0]['coverImageUrl']);
    }

    public function testDraftArticleIsNotReachableViaPublicItemEndpoint(): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get(EntityManagerInterface::class);

        $draft = (new NewsArticle())->setTitle('Draft actualite')->setContent('Draft content');
        $em->persist($draft);
        $em->flush();

        $client->request('GET', "/api/news/{$draft->getId()}");

        self::assertResponseStatusCodeSame(404);
    }

    public function testPublishedArticleIsReachableViaPublicItemEndpoint(): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get(EntityManagerInterface::class);

        $published = (new NewsArticle())->setTitle('Published actualite')->setContent('Published content')
            ->setStatus(NewsArticleStatus::Published);
        $em->persist($published);
        $em->flush();

        $client->request('GET', "/api/news/{$published->getId()}");

        self::assertResponseIsSuccessful();
        self::assertSame('Published actualite', json_decode($client->getResponse()->getContent(), true)['title']);
    }
}
