<?php

namespace App\Tests\Security;

use App\Repository\UserRepository;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class EmailVerificationTest extends WebTestCase
{
    protected function tearDown(): void
    {
        static::getContainer()->get(Connection::class)->executeStatement("DELETE FROM user WHERE email != 'admin@cms.dev'");

        parent::tearDown();
    }

    public function testValidTokenVerifiesTheAccount(): void
    {
        $client = static::createClient();

        $client->jsonRequest('POST', '/api/register', ['email' => 'verify-me@example.com', 'password' => 'correcthorsebatterystaple']);

        $userRepository = static::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => 'verify-me@example.com']);
        $token = $user->getVerificationToken();

        $client->request('GET', "/api/verify-email/{$token}");
        self::assertResponseIsSuccessful();
        self::assertTrue(json_decode($client->getResponse()->getContent(), true)['verified']);

        // The kernel (and its container/entity manager) reboots between
        // requests by default, so re-fetch rather than refresh() the
        // now-detached $user instance from before the reboot.
        $reloaded = static::getContainer()->get(UserRepository::class)->findOneBy(['email' => 'verify-me@example.com']);
        self::assertTrue($reloaded->isVerified());
        self::assertNull($reloaded->getVerificationToken());
        self::assertNotNull($reloaded->getVerifiedAt());
    }

    public function testAnUnknownTokenIsRejected(): void
    {
        $client = static::createClient();

        $client->request('GET', '/api/verify-email/does-not-exist');
        self::assertResponseStatusCodeSame(404);
    }
}
