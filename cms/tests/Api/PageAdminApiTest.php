<?php

namespace App\Tests\Api;

use App\Repository\UserRepository;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class PageAdminApiTest extends WebTestCase
{
    protected function tearDown(): void
    {
        static::getContainer()->get(Connection::class)->executeStatement('DELETE FROM page');

        parent::tearDown();
    }

    public function testFullCrudLifecycleAsAdmin(): void
    {
        $client = static::createClient();
        $admin = static::getContainer()->get(UserRepository::class)->findOneBy(['email' => 'admin@cms.dev']);
        $client->loginUser($admin);

        $client->jsonRequest('POST', '/api/admin/pages', [
            'title' => 'Test page',
            'content' => 'Test content',
        ]);
        self::assertResponseIsSuccessful();
        $created = json_decode($client->getResponse()->getContent(), true);
        self::assertSame('draft', $created['status']);
        self::assertSame('test-page', $created['slug']);
        $id = $created['id'];

        $client->request('GET', '/api/admin/pages');
        self::assertResponseIsSuccessful();
        $list = json_decode($client->getResponse()->getContent(), true);
        self::assertCount(1, $list);

        $client->request(
            'PATCH',
            "/api/admin/pages/{$id}",
            [],
            [],
            ['CONTENT_TYPE' => 'application/merge-patch+json'],
            json_encode(['status' => 'published']),
        );
        self::assertResponseIsSuccessful();
        self::assertSame('published', json_decode($client->getResponse()->getContent(), true)['status']);

        $client->request(
            'PATCH',
            "/api/admin/pages/{$id}",
            [],
            [],
            ['CONTENT_TYPE' => 'application/merge-patch+json'],
            json_encode(['status' => 'archived']),
        );
        self::assertResponseIsSuccessful();
        self::assertSame('archived', json_decode($client->getResponse()->getContent(), true)['status']);

        $client->request('DELETE', "/api/admin/pages/{$id}");
        self::assertResponseStatusCodeSame(204);

        $client->request('GET', "/api/admin/pages/{$id}");
        self::assertResponseStatusCodeSame(404);
    }

    public function testAnonymousCannotWrite(): void
    {
        $client = static::createClient();

        $client->jsonRequest('POST', '/api/admin/pages', [
            'title' => 'Should not be created',
            'content' => 'Should not be created',
        ]);

        self::assertResponseStatusCodeSame(401);
    }

    public function testAnonymousCannotListAdminPages(): void
    {
        $client = static::createClient();

        $client->request('GET', '/api/admin/pages');

        self::assertResponseStatusCodeSame(401);
    }
}
