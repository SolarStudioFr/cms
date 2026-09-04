<?php

namespace App\Tests\Api;

use App\Repository\UserRepository;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class RealisationAdminApiTest extends WebTestCase
{
    protected function tearDown(): void
    {
        static::getContainer()->get(Connection::class)->executeStatement('DELETE FROM realisation');

        parent::tearDown();
    }

    public function testFullCrudLifecycleAsAdmin(): void
    {
        $client = static::createClient();
        $admin = static::getContainer()->get(UserRepository::class)->findOneBy(['email' => 'admin@cms.dev']);
        $client->loginUser($admin);

        $client->jsonRequest('POST', '/api/admin/realisations', [
            'title' => 'Test realisation',
            'content' => 'Test content',
        ]);
        self::assertResponseIsSuccessful();
        $created = json_decode($client->getResponse()->getContent(), true);
        self::assertSame('draft', $created['status']);
        self::assertSame('test-realisation', $created['slug']);
        $id = $created['id'];

        $client->request('GET', '/api/admin/realisations');
        self::assertResponseIsSuccessful();
        $list = json_decode($client->getResponse()->getContent(), true);
        self::assertCount(1, $list);

        $client->request(
            'PATCH',
            "/api/admin/realisations/{$id}",
            [],
            [],
            ['CONTENT_TYPE' => 'application/merge-patch+json'],
            json_encode(['status' => 'published']),
        );
        self::assertResponseIsSuccessful();
        self::assertSame('published', json_decode($client->getResponse()->getContent(), true)['status']);

        $client->request(
            'PATCH',
            "/api/admin/realisations/{$id}",
            [],
            [],
            ['CONTENT_TYPE' => 'application/merge-patch+json'],
            json_encode(['status' => 'archived']),
        );
        self::assertResponseIsSuccessful();
        self::assertSame('archived', json_decode($client->getResponse()->getContent(), true)['status']);

        $client->request('DELETE', "/api/admin/realisations/{$id}");
        self::assertResponseStatusCodeSame(204);

        $client->request('GET', "/api/admin/realisations/{$id}");
        self::assertResponseStatusCodeSame(404);
    }

    public function testCoverImageRoundTrips(): void
    {
        $client = static::createClient();
        $admin = static::getContainer()->get(UserRepository::class)->findOneBy(['email' => 'admin@cms.dev']);
        $client->loginUser($admin);

        $client->jsonRequest('POST', '/api/admin/realisations', [
            'title' => 'With cover',
            'content' => 'Content',
            'coverImageUrl' => '/upload/img/webp/example.webp',
            'coverImageAlt' => 'Example',
        ]);
        self::assertResponseIsSuccessful();
        $created = json_decode($client->getResponse()->getContent(), true);
        self::assertSame('/upload/img/webp/example.webp', $created['coverImageUrl']);
        self::assertSame('Example', $created['coverImageAlt']);
    }

    public function testBuilderDataDefaultsToNullForTheFallbackEditor(): void
    {
        $client = static::createClient();
        $admin = static::getContainer()->get(UserRepository::class)->findOneBy(['email' => 'admin@cms.dev']);
        $client->loginUser($admin);

        $client->jsonRequest('POST', '/api/admin/realisations', ['title' => 'Fallback', 'content' => 'Plain text']);
        self::assertResponseIsSuccessful();
        self::assertNull(json_decode($client->getResponse()->getContent(), true)['builderData']);
    }

    public function testAnonymousCannotWrite(): void
    {
        $client = static::createClient();

        $client->jsonRequest('POST', '/api/admin/realisations', [
            'title' => 'Should not be created',
            'content' => 'Should not be created',
        ]);

        self::assertResponseStatusCodeSame(401);
    }

    public function testAnonymousCannotListAdminRealisations(): void
    {
        $client = static::createClient();

        $client->request('GET', '/api/admin/realisations');

        self::assertResponseStatusCodeSame(401);
    }
}
