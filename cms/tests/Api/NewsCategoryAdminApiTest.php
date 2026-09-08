<?php

namespace App\Tests\Api;

use App\Repository\UserRepository;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class NewsCategoryAdminApiTest extends WebTestCase
{
    protected function tearDown(): void
    {
        static::getContainer()->get(Connection::class)->executeStatement('DELETE FROM news_category');

        parent::tearDown();
    }

    public function testCreateAndListCategories(): void
    {
        $client = static::createClient();
        $admin = static::getContainer()->get(UserRepository::class)->findOneBy(['email' => 'admin@cms.dev']);
        $client->loginUser($admin);

        $client->jsonRequest('POST', '/api/admin/news/categories', ['name' => 'Événements']);
        self::assertResponseIsSuccessful();
        $created = json_decode($client->getResponse()->getContent(), true);
        self::assertSame('evenements', $created['slug']);

        $client->request('GET', '/api/admin/news/categories');
        self::assertResponseIsSuccessful();
        self::assertCount(1, json_decode($client->getResponse()->getContent(), true));
    }

    public function testAnonymousCannotWriteOrList(): void
    {
        $client = static::createClient();

        $client->jsonRequest('POST', '/api/admin/news/categories', ['name' => 'Should not be created']);
        self::assertResponseStatusCodeSame(401);

        $client->request('GET', '/api/admin/news/categories');
        self::assertResponseStatusCodeSame(401);
    }
}
