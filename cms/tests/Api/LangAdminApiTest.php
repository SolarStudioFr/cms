<?php

namespace App\Tests\Api;

use App\Repository\UserRepository;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Functional test of the i18n plugin's language backend (step 07): admin
 * CRUD, the public active-only listing, and seed data (FR/EN).
 */
class LangAdminApiTest extends WebTestCase
{
    protected function tearDown(): void
    {
        // Keep the seeded fr/en rows the migration inserts; only clean up
        // whatever a test itself added.
        static::getContainer()->get(Connection::class)->executeStatement("DELETE FROM lang WHERE code NOT IN ('fr', 'en')");

        parent::tearDown();
    }

    public function testSeedDataHasFrenchAndEnglish(): void
    {
        $client = static::createClient();
        $admin = static::getContainer()->get(UserRepository::class)->findOneBy(['email' => 'admin@cms.dev']);
        $client->loginUser($admin);

        $client->request('GET', '/api/admin/langs');
        self::assertResponseIsSuccessful();
        $langs = json_decode($client->getResponse()->getContent(), true);
        self::assertContains('fr', array_column($langs, 'code'));
        self::assertContains('en', array_column($langs, 'code'));
    }

    public function testFullCrudLifecycleAsAdmin(): void
    {
        $client = static::createClient();
        $admin = static::getContainer()->get(UserRepository::class)->findOneBy(['email' => 'admin@cms.dev']);
        $client->loginUser($admin);

        $client->jsonRequest('POST', '/api/admin/langs', ['code' => 'de', 'label' => 'Deutsch']);
        self::assertResponseIsSuccessful();
        $created = json_decode($client->getResponse()->getContent(), true);
        self::assertTrue($created['active']);
        $id = $created['id'];

        $client->request(
            'PATCH',
            "/api/admin/langs/{$id}",
            [],
            [],
            ['CONTENT_TYPE' => 'application/merge-patch+json'],
            json_encode(['active' => false]),
        );
        self::assertResponseIsSuccessful();
        self::assertFalse(json_decode($client->getResponse()->getContent(), true)['active']);

        $client->request('GET', '/api/langs');
        self::assertResponseIsSuccessful();
        self::assertNotContains('de', array_column(json_decode($client->getResponse()->getContent(), true), 'code'));

        $client->request('DELETE', "/api/admin/langs/{$id}");
        self::assertResponseStatusCodeSame(204);

        $client->request('GET', "/api/admin/langs/{$id}");
        self::assertResponseStatusCodeSame(404);
    }

    public function testPublicListingOnlyReturnsActiveLanguages(): void
    {
        $client = static::createClient();

        $client->request('GET', '/api/langs');
        self::assertResponseIsSuccessful();
        $codes = array_column(json_decode($client->getResponse()->getContent(), true), 'code');
        self::assertContains('fr', $codes);
        self::assertContains('en', $codes);
    }

    public function testAnonymousCannotWrite(): void
    {
        $client = static::createClient();

        $client->jsonRequest('POST', '/api/admin/langs', ['code' => 'de', 'label' => 'Deutsch']);
        self::assertResponseStatusCodeSame(401);
    }
}
