<?php

namespace App\Tests\Api;

use App\Repository\UserRepository;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class AdminMenuConfigApiTest extends WebTestCase
{
    protected function tearDown(): void
    {
        static::getContainer()->get(Connection::class)->executeStatement('DELETE FROM admin_menu_config');

        parent::tearDown();
    }

    public function testGetAutoCreatesTheSingletonEmpty(): void
    {
        $client = static::createClient();
        $admin = static::getContainer()->get(UserRepository::class)->findOneBy(['email' => 'admin@cms.dev']);
        $client->loginUser($admin);

        $client->request('GET', '/api/admin/admin-menu-config');
        self::assertResponseIsSuccessful();
        self::assertSame([], json_decode($client->getResponse()->getContent(), true)['items']);
    }

    public function testPatchReplacesTheOrderAndPersistsOnTheSameRow(): void
    {
        $client = static::createClient();
        $admin = static::getContainer()->get(UserRepository::class)->findOneBy(['email' => 'admin@cms.dev']);
        $client->loginUser($admin);

        $order = [
            ['type' => 'item', 'key' => 'dashboard'],
            ['type' => 'separator'],
            ['type' => 'item', 'key' => 'plugin:page'],
        ];

        $client->jsonRequest('PATCH', '/api/admin/admin-menu-config', ['items' => $order]);
        self::assertResponseIsSuccessful();
        // assertEquals, not assertSame: MySQL's native JSON type doesn't
        // preserve object key insertion order, only equivalent content.
        self::assertEquals($order, json_decode($client->getResponse()->getContent(), true)['items']);

        $client->request('GET', '/api/admin/admin-menu-config');
        self::assertEquals($order, json_decode($client->getResponse()->getContent(), true)['items']);
    }

    public function testAnonymousCannotAccess(): void
    {
        $client = static::createClient();

        $client->request('GET', '/api/admin/admin-menu-config');

        self::assertResponseStatusCodeSame(401);
    }
}
