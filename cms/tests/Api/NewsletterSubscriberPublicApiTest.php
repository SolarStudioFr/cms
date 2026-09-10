<?php

namespace App\Tests\Api;

use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class NewsletterSubscriberPublicApiTest extends WebTestCase
{
    protected function tearDown(): void
    {
        static::getContainer()->get(Connection::class)->executeStatement('DELETE FROM newsletter_subscriber');

        parent::tearDown();
    }

    public function testAnonymousVisitorCanSubscribe(): void
    {
        $client = static::createClient();

        $client->jsonRequest('POST', '/api/newsletter/subscribers', ['email' => 'visitor@example.com']);
        self::assertResponseIsSuccessful();
        self::assertSame('visitor@example.com', json_decode($client->getResponse()->getContent(), true)['email']);
    }

    public function testResubmittingTheSameEmailIsIdempotent(): void
    {
        $client = static::createClient();

        $client->jsonRequest('POST', '/api/newsletter/subscribers', ['email' => 'visitor@example.com']);
        $firstId = json_decode($client->getResponse()->getContent(), true)['id'];

        $client->jsonRequest('POST', '/api/newsletter/subscribers', ['email' => 'visitor@example.com']);
        self::assertResponseIsSuccessful();
        $secondId = json_decode($client->getResponse()->getContent(), true)['id'];

        self::assertSame($firstId, $secondId);
    }

    public function testInvalidEmailIsRejected(): void
    {
        $client = static::createClient();

        $client->jsonRequest('POST', '/api/newsletter/subscribers', ['email' => 'not-an-email']);
        self::assertResponseStatusCodeSame(422);
    }

    /** Step 58 follow-up: name/locale captured at signup, both optional. */
    public function testNameAndLocaleAreCapturedWhenProvided(): void
    {
        $client = static::createClient();

        $client->jsonRequest('POST', '/api/newsletter/subscribers', [
            'email' => 'visitor@example.com',
            'name' => 'Jane Visitor',
            'locale' => 'en',
        ]);
        self::assertResponseIsSuccessful();
        $subscriber = json_decode($client->getResponse()->getContent(), true);
        self::assertSame('Jane Visitor', $subscriber['name']);
        self::assertSame('en', $subscriber['locale']);
    }

    public function testNameAndLocaleAreOptional(): void
    {
        $client = static::createClient();

        $client->jsonRequest('POST', '/api/newsletter/subscribers', ['email' => 'visitor@example.com']);
        self::assertResponseIsSuccessful();
        $subscriber = json_decode($client->getResponse()->getContent(), true);
        self::assertNull($subscriber['name']);
        self::assertNull($subscriber['locale']);
    }
}
