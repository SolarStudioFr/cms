<?php

namespace App\Tests\Security;

use App\Repository\UserRepository;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Test\MailerAssertionsTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class RegistrationTest extends WebTestCase
{
    use MailerAssertionsTrait;

    protected function tearDown(): void
    {
        static::getContainer()->get(Connection::class)->executeStatement("DELETE FROM user WHERE email != 'admin@cms.dev'");

        parent::tearDown();
    }

    public function testVisitorCanRegisterAndReceivesAVerificationEmail(): void
    {
        $client = static::createClient();

        $client->jsonRequest('POST', '/api/register', [
            'email' => 'newuser@example.com',
            'password' => 'correcthorsebatterystaple',
        ]);

        self::assertResponseStatusCodeSame(201);
        self::assertSame('newuser@example.com', json_decode($client->getResponse()->getContent(), true)['email']);

        $user = static::getContainer()->get(UserRepository::class)->findOneBy(['email' => 'newuser@example.com']);
        self::assertNotNull($user);
        self::assertFalse($user->isVerified());
        self::assertNotNull($user->getVerificationToken());

        self::assertEmailCount(1);
        self::assertEmailAddressContains(self::getMailerMessage(), 'To', 'newuser@example.com');
    }

    public function testRegisteringWithAnInvalidEmailIsRejected(): void
    {
        $client = static::createClient();

        $client->jsonRequest('POST', '/api/register', ['email' => 'not-an-email', 'password' => 'correcthorsebatterystaple']);
        self::assertResponseStatusCodeSame(422);
    }

    public function testRegisteringWithAShortPasswordIsRejected(): void
    {
        $client = static::createClient();

        $client->jsonRequest('POST', '/api/register', ['email' => 'shortpass@example.com', 'password' => 'short']);
        self::assertResponseStatusCodeSame(422);
    }

    public function testRegisteringWithAnAlreadyUsedEmailIsRejected(): void
    {
        $client = static::createClient();

        $client->jsonRequest('POST', '/api/register', ['email' => 'admin@cms.dev', 'password' => 'correcthorsebatterystaple']);
        self::assertResponseStatusCodeSame(409);
    }
}
