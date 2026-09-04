<?php

namespace App\Tests\Api;

use App\Repository\UserRepository;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class UserAdminApiTest extends WebTestCase
{
    protected function tearDown(): void
    {
        static::getContainer()->get(Connection::class)->executeStatement("DELETE FROM user WHERE email != 'admin@cms.dev'");

        parent::tearDown();
    }

    public function testFullLifecycleAsAdmin(): void
    {
        $client = static::createClient();
        $admin = static::getContainer()->get(UserRepository::class)->findOneBy(['email' => 'admin@cms.dev']);
        $client->loginUser($admin);

        $client->jsonRequest('POST', '/api/admin/users', [
            'email' => 'newmember@example.com',
            'password' => 'correcthorsebatterystaple',
        ]);
        self::assertResponseStatusCodeSame(201);
        $created = json_decode($client->getResponse()->getContent(), true);
        self::assertTrue($created['verified']);
        self::assertSame([], array_diff($created['roles'], ['ROLE_USER']));
        $id = $created['id'];

        $client->request('GET', '/api/admin/users');
        self::assertResponseIsSuccessful();
        $list = json_decode($client->getResponse()->getContent(), true);
        self::assertGreaterThanOrEqual(2, \count($list));

        $client->request(
            'PATCH',
            "/api/admin/users/{$id}",
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['roles' => ['ROLE_USER', 'ROLE_SUPER_ADMIN']]),
        );
        self::assertResponseIsSuccessful();
        self::assertContains('ROLE_SUPER_ADMIN', json_decode($client->getResponse()->getContent(), true)['roles']);

        $client->request(
            'PATCH',
            "/api/admin/users/{$id}",
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['verified' => false]),
        );
        self::assertResponseIsSuccessful();
        self::assertFalse(json_decode($client->getResponse()->getContent(), true)['verified']);

        $client->request('DELETE', "/api/admin/users/{$id}");
        self::assertResponseStatusCodeSame(204);
    }

    public function testAdminCannotDeleteTheirOwnAccount(): void
    {
        $client = static::createClient();
        $admin = static::getContainer()->get(UserRepository::class)->findOneBy(['email' => 'admin@cms.dev']);
        $client->loginUser($admin);

        $client->request('DELETE', "/api/admin/users/{$admin->getId()}");
        self::assertResponseStatusCodeSame(400);
    }

    public function testCreatingWithAnAlreadyUsedEmailIsRejected(): void
    {
        $client = static::createClient();
        $admin = static::getContainer()->get(UserRepository::class)->findOneBy(['email' => 'admin@cms.dev']);
        $client->loginUser($admin);

        $client->jsonRequest('POST', '/api/admin/users', ['email' => 'admin@cms.dev', 'password' => 'correcthorsebatterystaple']);
        self::assertResponseStatusCodeSame(409);
    }

    public function testListingRequiresAuthentication(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/admin/users');
        self::assertResponseStatusCodeSame(401);
    }
}
