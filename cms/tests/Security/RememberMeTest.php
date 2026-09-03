<?php

namespace App\Tests\Security;

use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class RememberMeTest extends WebTestCase
{
    protected function tearDown(): void
    {
        static::getContainer()->get(Connection::class)->executeStatement('DELETE FROM rememberme_token');

        parent::tearDown();
    }

    public function testRememberMeSetsAPersistentCookieAndDatabaseToken(): void
    {
        $client = static::createClient();

        $client->jsonRequest('POST', '/api/login', [
            'email' => 'admin@cms.dev',
            'password' => '1234567890',
            '_remember_me' => true,
        ]);

        self::assertResponseIsSuccessful();

        $cookie = $client->getCookieJar()->get('REMEMBERME');
        self::assertNotNull($cookie, 'Expected a REMEMBERME cookie to be set.');

        $count = (int) static::getContainer()->get(Connection::class)
            ->fetchOne('SELECT COUNT(*) FROM rememberme_token WHERE username = ?', ['admin@cms.dev']);
        self::assertSame(1, $count);
    }
}
