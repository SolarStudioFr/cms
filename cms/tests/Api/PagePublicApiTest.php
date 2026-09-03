<?php

namespace App\Tests\Api;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Plugin\Page\Entity\Page;
use Plugin\Page\Entity\PageStatus;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class PagePublicApiTest extends WebTestCase
{
    protected function tearDown(): void
    {
        static::getContainer()->get(Connection::class)->executeStatement('DELETE FROM page');

        parent::tearDown();
    }

    public function testOnlyPublishedPagesAreListedPublicly(): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get(EntityManagerInterface::class);

        $draft = (new Page())->setTitle('Draft page')->setContent('Draft content');
        $published = (new Page())->setTitle('Published page')->setContent('Published content')
            ->setStatus(PageStatus::Published);
        $archived = (new Page())->setTitle('Archived page')->setContent('Archived content')
            ->setStatus(PageStatus::Archived);

        foreach ([$draft, $published, $archived] as $page) {
            $em->persist($page);
        }
        $em->flush();

        $client->request('GET', '/api/pages');

        self::assertResponseIsSuccessful();
        $list = json_decode($client->getResponse()->getContent(), true);
        self::assertCount(1, $list);
        self::assertSame('Published page', $list[0]['title']);
        self::assertSame('published', $list[0]['status']);
    }

    public function testDraftPageIsNotReachableViaPublicItemEndpoint(): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get(EntityManagerInterface::class);

        $draft = (new Page())->setTitle('Draft page')->setContent('Draft content');
        $em->persist($draft);
        $em->flush();

        $client->request('GET', "/api/pages/{$draft->getId()}");

        self::assertResponseStatusCodeSame(404);
    }
}
