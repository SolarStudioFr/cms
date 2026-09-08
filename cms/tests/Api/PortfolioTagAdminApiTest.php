<?php

namespace App\Tests\Api;

use App\Repository\UserRepository;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class PortfolioTagAdminApiTest extends WebTestCase
{
    protected function tearDown(): void
    {
        static::getContainer()->get(Connection::class)->executeStatement('DELETE FROM portfolio_tag');

        parent::tearDown();
    }

    public function testCreateAndListTags(): void
    {
        $client = static::createClient();
        $admin = static::getContainer()->get(UserRepository::class)->findOneBy(['email' => 'admin@cms.dev']);
        $client->loginUser($admin);

        $client->jsonRequest('POST', '/api/admin/portfolio/tags', ['name' => 'Architecture']);
        self::assertResponseIsSuccessful();
        $created = json_decode($client->getResponse()->getContent(), true);
        self::assertSame('architecture', $created['slug']);

        $client->request('GET', '/api/admin/portfolio/tags');
        self::assertResponseIsSuccessful();
        self::assertCount(1, json_decode($client->getResponse()->getContent(), true));
    }

    public function testAnonymousCannotWriteOrList(): void
    {
        $client = static::createClient();

        $client->jsonRequest('POST', '/api/admin/portfolio/tags', ['name' => 'Should not be created']);
        self::assertResponseStatusCodeSame(401);

        $client->request('GET', '/api/admin/portfolio/tags');
        self::assertResponseStatusCodeSame(401);
    }
}
