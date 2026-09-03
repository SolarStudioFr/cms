<?php

namespace App\Tests\Security;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class JsonLoginTest extends WebTestCase
{
    public function testSuccessfulLogin(): void
    {
        $client = static::createClient();

        $client->jsonRequest('POST', '/api/login', [
            'email' => 'admin@cms.dev',
            'password' => '1234567890',
        ]);

        self::assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);
        self::assertSame('admin@cms.dev', $data['email']);
        self::assertContains('ROLE_SUPER_ADMIN', $data['roles']);

        $client->request('GET', '/api/me');
        self::assertResponseIsSuccessful();
    }

    public function testWrongPasswordIsRejected(): void
    {
        $client = static::createClient();

        $client->jsonRequest('POST', '/api/login', [
            'email' => 'admin@cms.dev',
            'password' => 'not-the-right-password',
        ]);

        self::assertResponseStatusCodeSame(401);
    }

    public function testMeWithoutSessionIsUnauthorized(): void
    {
        $client = static::createClient();

        $client->request('GET', '/api/me');

        self::assertResponseStatusCodeSame(401);
    }
}
