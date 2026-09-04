<?php

namespace App\Tests\Api;

use App\Repository\UserRepository;
use Doctrine\DBAL\Connection;
use Plugin\I18n\Service\TranslationPoConverter;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Functional test of the translation management backend (step 08): CRUD
 * over individual translations plus PO export/import, through the real
 * HTTP layer.
 */
class TranslationAdminApiTest extends WebTestCase
{
    protected function tearDown(): void
    {
        static::getContainer()->get(Connection::class)->executeStatement('DELETE FROM translation');

        parent::tearDown();
    }

    public function testUpsertListAndDelete(): void
    {
        $client = static::createClient();
        $admin = static::getContainer()->get(UserRepository::class)->findOneBy(['email' => 'admin@cms.dev']);
        $client->loginUser($admin);

        $client->jsonRequest('POST', '/api/admin/translations', ['lang' => 'fr', 'key' => 'hello', 'value' => 'Bonjour']);
        self::assertResponseStatusCodeSame(201);
        $created = json_decode($client->getResponse()->getContent(), true);
        self::assertSame('Bonjour', $created['value']);
        self::assertSame('messages', $created['domain']);
        $id = $created['id'];

        // Upserting the same key again updates it in place rather than duplicating.
        $client->jsonRequest('POST', '/api/admin/translations', ['lang' => 'fr', 'key' => 'hello', 'value' => 'Salut']);
        self::assertResponseIsSuccessful();

        $client->request('GET', '/api/admin/translations?lang=fr');
        self::assertResponseIsSuccessful();
        $list = json_decode($client->getResponse()->getContent(), true);
        self::assertCount(1, $list);
        self::assertSame('Salut', $list[0]['value']);

        $client->request('DELETE', "/api/admin/translations/{$id}");
        self::assertResponseStatusCodeSame(204);

        $client->request('GET', '/api/admin/translations?lang=fr');
        self::assertCount(0, json_decode($client->getResponse()->getContent(), true));
    }

    public function testExportProducesAValidPoFile(): void
    {
        $client = static::createClient();
        $admin = static::getContainer()->get(UserRepository::class)->findOneBy(['email' => 'admin@cms.dev']);
        $client->loginUser($admin);

        $client->jsonRequest('POST', '/api/admin/translations', ['lang' => 'fr', 'key' => 'hello', 'value' => 'Bonjour']);
        self::assertResponseIsSuccessful();

        $client->request('GET', '/api/admin/translations/export?lang=fr');
        self::assertResponseIsSuccessful();
        $content = $client->getResponse()->getContent();
        self::assertStringContainsString('msgid "hello"', $content);
        self::assertStringContainsString('msgstr "Bonjour"', $content);
        self::assertStringContainsString('attachment', $client->getResponse()->headers->get('Content-Disposition'));
    }

    public function testImportUpsertsEveryEntryInThePoFile(): void
    {
        $client = static::createClient();
        $admin = static::getContainer()->get(UserRepository::class)->findOneBy(['email' => 'admin@cms.dev']);
        $client->loginUser($admin);

        $poPath = tempnam(sys_get_temp_dir(), 'i18n_test_').'.po';
        file_put_contents($poPath, <<<'PO'
            msgid ""
            msgstr ""
            "Content-Type: text/plain; charset=UTF-8\n"

            msgid "hello"
            msgstr "Bonjour"

            msgid "bye"
            msgstr "Au revoir"
            PO);

        $client->request(
            'POST',
            '/api/admin/translations/import',
            ['lang' => 'fr', 'domain' => 'messages'],
            ['file' => new UploadedFile($poPath, 'messages.fr.po', 'text/x-gettext-translation', null, true)],
        );
        self::assertResponseIsSuccessful();
        self::assertSame(['imported' => 2], json_decode($client->getResponse()->getContent(), true));

        $client->request('GET', '/api/admin/translations?lang=fr');
        $list = json_decode($client->getResponse()->getContent(), true);
        self::assertCount(2, $list);
        self::assertSame('Bonjour', current(array_filter($list, static fn ($t) => 'hello' === $t['key']))['value']);
    }

    public function testAnonymousCannotAccess(): void
    {
        $client = static::createClient();

        $client->request('GET', '/api/admin/translations?lang=fr');
        self::assertResponseStatusCodeSame(401);
    }

    public function testPoConverterRoundTrips(): void
    {
        $converter = new TranslationPoConverter();

        $po = $converter->export('fr', 'messages', ['hello' => 'Bonjour']);
        self::assertStringContainsString('msgid "hello"', $po);

        $tmpPath = tempnam(sys_get_temp_dir(), 'i18n_test_');
        file_put_contents($tmpPath, $po);
        $messages = $converter->import($tmpPath, 'fr', 'messages');
        unlink($tmpPath);

        self::assertSame(['hello' => 'Bonjour'], $messages);
    }
}
