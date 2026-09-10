<?php

namespace App\Tests\Api;

use App\Repository\UserRepository;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Functional test of the generic content-translation admin backend (step
 * 58): App\Controller\Admin\ContentTranslationController, the shared store
 * behind ContentTranslationPanel.jsx. Deliberately exercised with a made-up
 * entityType ("widget") rather than "page" - this mechanism must not care
 * which content type it's storing translations for.
 */
class ContentTranslationAdminApiTest extends WebTestCase
{
    protected function tearDown(): void
    {
        static::getContainer()->get(Connection::class)->executeStatement('DELETE FROM content_translation');

        parent::tearDown();
    }

    public function testUpsertThenListGroupsByLocale(): void
    {
        $client = static::createClient();
        $admin = static::getContainer()->get(UserRepository::class)->findOneBy(['email' => 'admin@cms.dev']);
        $client->loginUser($admin);

        $client->jsonRequest('PATCH', '/api/admin/content-translations', [
            'entityType' => 'widget',
            'entityId' => 42,
            'locale' => 'en',
            'values' => ['title' => 'EN title', 'content' => 'EN content'],
        ]);
        self::assertResponseIsSuccessful();
        self::assertSame(['title' => 'EN title', 'content' => 'EN content'], json_decode($client->getResponse()->getContent(), true));

        $client->jsonRequest('PATCH', '/api/admin/content-translations', [
            'entityType' => 'widget',
            'entityId' => 42,
            'locale' => 'de',
            'values' => ['title' => 'DE title'],
        ]);
        self::assertResponseIsSuccessful();

        $client->request('GET', '/api/admin/content-translations?entityType=widget&entityId=42');
        self::assertResponseIsSuccessful();
        $byLocale = json_decode($client->getResponse()->getContent(), true);
        self::assertEqualsCanonicalizing(['title' => 'EN title', 'content' => 'EN content'], $byLocale['en']);
        self::assertSame(['title' => 'DE title'], $byLocale['de']);
    }

    public function testUpsertingAgainReplacesTheStoredValueRatherThanDuplicating(): void
    {
        $client = static::createClient();
        $admin = static::getContainer()->get(UserRepository::class)->findOneBy(['email' => 'admin@cms.dev']);
        $client->loginUser($admin);

        $client->jsonRequest('PATCH', '/api/admin/content-translations', [
            'entityType' => 'widget', 'entityId' => 1, 'locale' => 'en', 'values' => ['title' => 'First'],
        ]);
        $client->jsonRequest('PATCH', '/api/admin/content-translations', [
            'entityType' => 'widget', 'entityId' => 1, 'locale' => 'en', 'values' => ['title' => 'Second'],
        ]);

        $client->request('GET', '/api/admin/content-translations?entityType=widget&entityId=1');
        $byLocale = json_decode($client->getResponse()->getContent(), true);
        self::assertSame(['title' => 'Second'], $byLocale['en']);
    }

    public function testAnonymousCannotAccess(): void
    {
        $client = static::createClient();

        $client->request('GET', '/api/admin/content-translations?entityType=widget&entityId=1');
        self::assertResponseStatusCodeSame(401);
    }
}
