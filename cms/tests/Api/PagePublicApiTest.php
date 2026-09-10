<?php

namespace App\Tests\Api;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\ContentTranslation;
use App\Entity\Page;
use App\Entity\PageStatus;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class PagePublicApiTest extends WebTestCase
{
    protected function tearDown(): void
    {
        static::getContainer()->get(Connection::class)->executeStatement('DELETE FROM content_translation');
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

    /** Step 58: a ?locale= query param overlays App\Service\ContentTranslator's stored translations onto the response. */
    public function testLocaleQueryParamOverlaysTranslatedFields(): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get(EntityManagerInterface::class);

        $page = (new Page())->setTitle('Titre FR')->setContent('Contenu FR')->setStatus(PageStatus::Published);
        $em->persist($page);
        $em->flush();

        $translation = (new ContentTranslation())
            ->setEntityType('page')->setEntityId($page->getId())->setField('title')->setLocale('en')->setValue('EN title');
        $em->persist($translation);
        $em->flush();

        $client->request('GET', "/api/pages/{$page->getId()}?locale=en");
        self::assertResponseIsSuccessful();
        $item = json_decode($client->getResponse()->getContent(), true);
        self::assertSame('EN title', $item['title']);
        // "content" has no stored translation - falls back to the base value.
        self::assertSame('Contenu FR', $item['content']);

        $client->request('GET', '/api/pages?locale=en');
        self::assertResponseIsSuccessful();
        $list = json_decode($client->getResponse()->getContent(), true);
        self::assertSame('EN title', $list[0]['title']);
    }

    public function testNoLocaleQueryParamReturnsBaseFields(): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get(EntityManagerInterface::class);

        $page = (new Page())->setTitle('Titre FR')->setContent('Contenu FR')->setStatus(PageStatus::Published);
        $em->persist($page);
        $em->flush();

        $translation = (new ContentTranslation())
            ->setEntityType('page')->setEntityId($page->getId())->setField('title')->setLocale('en')->setValue('EN title');
        $em->persist($translation);
        $em->flush();

        $client->request('GET', "/api/pages/{$page->getId()}");
        self::assertResponseIsSuccessful();
        $item = json_decode($client->getResponse()->getContent(), true);
        self::assertSame('Titre FR', $item['title']);
    }
}
