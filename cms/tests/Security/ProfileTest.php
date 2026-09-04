<?php

namespace App\Tests\Security;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ProfileTest extends WebTestCase
{
    public function testUserCanChangeTheirOwnPassword(): void
    {
        $client = static::createClient();
        $user = static::getContainer()->get(UserRepository::class)->findOneBy(['email' => 'admin@cms.dev']);
        $client->loginUser($user);

        $client->request(
            'PATCH',
            '/api/profile',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['currentPassword' => '1234567890', 'newPassword' => 'brandnewpassword']),
        );

        self::assertResponseIsSuccessful();
        self::assertSame('admin@cms.dev', json_decode($client->getResponse()->getContent(), true)['email']);

        // Restore the original password so other tests relying on the
        // fixture's default credentials aren't affected.
        $client->request(
            'PATCH',
            '/api/profile',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['currentPassword' => 'brandnewpassword', 'newPassword' => '1234567890']),
        );
        self::assertResponseIsSuccessful();
    }

    public function testWrongCurrentPasswordIsRejected(): void
    {
        $client = static::createClient();
        $user = static::getContainer()->get(UserRepository::class)->findOneBy(['email' => 'admin@cms.dev']);
        $client->loginUser($user);

        $client->request(
            'PATCH',
            '/api/profile',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['currentPassword' => 'not-the-right-password', 'newPassword' => 'brandnewpassword']),
        );

        self::assertResponseStatusCodeSame(422);
    }

    public function testUpdatingProfileRequiresAuthentication(): void
    {
        $client = static::createClient();

        $client->request(
            'PATCH',
            '/api/profile',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['currentPassword' => 'anything']),
        );

        self::assertResponseStatusCodeSame(401);
    }
}
