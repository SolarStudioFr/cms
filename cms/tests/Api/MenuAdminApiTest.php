<?php

namespace App\Tests\Api;

use App\Repository\UserRepository;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class MenuAdminApiTest extends WebTestCase
{
    protected function tearDown(): void
    {
        static::getContainer()->get(Connection::class)->executeStatement('DELETE FROM menu');

        parent::tearDown();
    }

    public function testFullCrudLifecycleAsAdmin(): void
    {
        $client = static::createClient();
        $admin = static::getContainer()->get(UserRepository::class)->findOneBy(['email' => 'admin@cms.dev']);
        $client->loginUser($admin);

        $items = [
            ['id' => 'a', 'type' => 'link', 'label' => 'Accueil', 'url' => '/', 'target' => '_self'],
            ['id' => 'b', 'type' => 'separator'],
            ['id' => 'c', 'type' => 'link', 'label' => 'Contact', 'url' => 'https://example.com', 'target' => '_blank'],
        ];

        $client->jsonRequest('POST', '/api/admin/menus', ['name' => 'Menu principal', 'items' => $items]);
        self::assertResponseIsSuccessful();
        $created = json_decode($client->getResponse()->getContent(), true);
        self::assertNull($created['hookName']);
        // assertEquals, not assertSame: MySQL's native JSON type doesn't
        // preserve object key insertion order, only equivalent content.
        self::assertEquals($items, $created['items']);
        $id = $created['id'];

        $client->request('GET', '/api/admin/menus');
        self::assertResponseIsSuccessful();
        self::assertCount(1, json_decode($client->getResponse()->getContent(), true));

        $client->request(
            'PATCH',
            "/api/admin/menus/{$id}",
            [],
            [],
            ['CONTENT_TYPE' => 'application/merge-patch+json'],
            json_encode(['hookName' => 'header-menu']),
        );
        self::assertResponseIsSuccessful();
        self::assertSame('header-menu', json_decode($client->getResponse()->getContent(), true)['hookName']);

        $client->request('DELETE', "/api/admin/menus/{$id}");
        self::assertResponseStatusCodeSame(204);

        $client->request('GET', "/api/admin/menus/{$id}");
        self::assertResponseStatusCodeSame(404);
    }

    public function testPublicListingOnlyReturnsMenusAttachedToAHook(): void
    {
        $client = static::createClient();
        $admin = static::getContainer()->get(UserRepository::class)->findOneBy(['email' => 'admin@cms.dev']);
        $client->loginUser($admin);

        $client->jsonRequest('POST', '/api/admin/menus', ['name' => 'Unattached', 'items' => []]);
        self::assertResponseIsSuccessful();

        $client->jsonRequest('POST', '/api/admin/menus', [
            'name' => 'Header',
            'hookName' => 'header-menu',
            'items' => [['id' => 'a', 'type' => 'link', 'label' => 'Home', 'url' => '/', 'target' => '_self']],
        ]);
        self::assertResponseIsSuccessful();

        $client->request('GET', '/api/menus');
        self::assertResponseIsSuccessful();
        $public = json_decode($client->getResponse()->getContent(), true);
        self::assertCount(1, $public);
        self::assertSame('header-menu', $public[0]['hookName']);
    }

    public function testAnonymousCannotWrite(): void
    {
        $client = static::createClient();

        $client->jsonRequest('POST', '/api/admin/menus', ['name' => 'Should not be created', 'items' => []]);

        self::assertResponseStatusCodeSame(401);
    }

    public function testAnonymousCannotListAdminMenus(): void
    {
        $client = static::createClient();

        $client->request('GET', '/api/admin/menus');

        self::assertResponseStatusCodeSame(401);
    }
}
