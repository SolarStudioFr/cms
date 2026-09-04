<?php

namespace App\Tests\Api;

use App\Repository\UserRepository;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class SiteConfigAdminApiTest extends WebTestCase
{
    protected function tearDown(): void
    {
        static::getContainer()->get(Connection::class)->executeStatement('DELETE FROM site_config');

        parent::tearDown();
    }

    public function testGetAutoCreatesTheSingletonWithDefaults(): void
    {
        $client = static::createClient();
        $admin = static::getContainer()->get(UserRepository::class)->findOneBy(['email' => 'admin@cms.dev']);
        $client->loginUser($admin);

        $client->request('GET', '/api/admin/site-config');
        self::assertResponseIsSuccessful();
        $config = json_decode($client->getResponse()->getContent(), true);
        self::assertSame('Solar CMS', $config['siteName']);
        self::assertNull($config['smtpHost']);
    }

    public function testPatchUpdatesTheSameSingletonRow(): void
    {
        $client = static::createClient();
        $admin = static::getContainer()->get(UserRepository::class)->findOneBy(['email' => 'admin@cms.dev']);
        $client->loginUser($admin);

        $client->request(
            'PATCH',
            '/api/admin/site-config',
            [],
            [],
            ['CONTENT_TYPE' => 'application/merge-patch+json'],
            json_encode(['siteName' => 'My Site', 'smtpHost' => 'smtp.example.com', 'smtpPort' => 587]),
        );
        self::assertResponseIsSuccessful();
        $first = json_decode($client->getResponse()->getContent(), true);
        self::assertSame('My Site', $first['siteName']);
        self::assertSame('smtp.example.com', $first['smtpHost']);
        self::assertSame(587, $first['smtpPort']);

        $client->request('GET', '/api/admin/site-config');
        $second = json_decode($client->getResponse()->getContent(), true);
        self::assertSame('My Site', $second['siteName']);

        $client->request('GET', '/api/site-config');
        self::assertResponseIsSuccessful();
        $public = json_decode($client->getResponse()->getContent(), true);
        self::assertSame('My Site', $public['siteName']);
        self::assertArrayNotHasKey('smtpHost', $public);
    }

    public function testTestMailEndpointSendsAnEmail(): void
    {
        $client = static::createClient();
        $admin = static::getContainer()->get(UserRepository::class)->findOneBy(['email' => 'admin@cms.dev']);
        $client->loginUser($admin);

        $client->jsonRequest('POST', '/api/admin/site-config/test-mail', ['to' => 'someone@example.com']);
        self::assertResponseIsSuccessful();
        self::assertTrue(json_decode($client->getResponse()->getContent(), true)['success']);
    }

    public function testAdminEndpointsRequireAuthentication(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/admin/site-config');
        self::assertResponseStatusCodeSame(401);
    }
}
