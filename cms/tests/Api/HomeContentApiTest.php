<?php

namespace App\Tests\Api;

use App\Repository\UserRepository;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Functional test of the Homepage plugin's homepage content backend (step 21):
 * singleton auto-creation, admin get/patch, and the public read.
 */
class HomeContentApiTest extends WebTestCase
{
    protected function tearDown(): void
    {
        static::getContainer()->get(Connection::class)->executeStatement('DELETE FROM home_content');

        parent::tearDown();
    }

    public function testAdminGetAutoCreatesTheSingletonEmpty(): void
    {
        $client = static::createClient();
        $admin = static::getContainer()->get(UserRepository::class)->findOneBy(['email' => 'admin@cms.dev']);
        $client->loginUser($admin);

        $client->request('GET', '/api/admin/homepage');

        self::assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);
        self::assertSame('', $data['content']);
        self::assertNull($data['builderData']);
    }

    public function testAdminCanPatchTheSingletonAndPublicReadReflectsIt(): void
    {
        $client = static::createClient();
        $admin = static::getContainer()->get(UserRepository::class)->findOneBy(['email' => 'admin@cms.dev']);
        $client->loginUser($admin);

        $client->request(
            'PATCH',
            '/api/admin/homepage',
            [],
            [],
            ['CONTENT_TYPE' => 'application/merge-patch+json'],
            json_encode(['content' => '<h1>Bienvenue</h1>']),
        );
        self::assertResponseIsSuccessful();
        self::assertSame('<h1>Bienvenue</h1>', json_decode($client->getResponse()->getContent(), true)['content']);

        // Patching again must update the same row, not create a second one.
        $client->request('GET', '/api/admin/homepage');
        $afterFirstPatch = json_decode($client->getResponse()->getContent(), true);

        $client->request(
            'PATCH',
            '/api/admin/homepage',
            [],
            [],
            ['CONTENT_TYPE' => 'application/merge-patch+json'],
            json_encode(['content' => '<h1>Bienvenue v2</h1>']),
        );
        self::assertResponseIsSuccessful();
        $afterSecondPatch = json_decode($client->getResponse()->getContent(), true);
        self::assertSame($afterFirstPatch['id'], $afterSecondPatch['id']);

        $client->request('GET', '/api/homepage');
        self::assertResponseIsSuccessful();
        self::assertSame('<h1>Bienvenue v2</h1>', json_decode($client->getResponse()->getContent(), true)['content']);
    }

    public function testAnonymousCanReadPublicHome(): void
    {
        $client = static::createClient();

        $client->request('GET', '/api/homepage');

        self::assertResponseIsSuccessful();
    }

    public function testAnonymousCannotWrite(): void
    {
        $client = static::createClient();

        $client->request(
            'PATCH',
            '/api/admin/homepage',
            [],
            [],
            ['CONTENT_TYPE' => 'application/merge-patch+json'],
            json_encode(['content' => 'Should not be saved']),
        );

        self::assertResponseStatusCodeSame(401);
    }

    public function testAnonymousCannotReadAdminHome(): void
    {
        $client = static::createClient();

        $client->request('GET', '/api/admin/homepage');

        self::assertResponseStatusCodeSame(401);
    }
}
