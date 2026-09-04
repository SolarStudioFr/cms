<?php

namespace App\Tests\Api;

use App\Repository\UserRepository;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class NewsletterSubscriberAdminApiTest extends WebTestCase
{
    protected function tearDown(): void
    {
        static::getContainer()->get(Connection::class)->executeStatement('DELETE FROM newsletter_campaign_send');
        static::getContainer()->get(Connection::class)->executeStatement('DELETE FROM newsletter_subscriber');

        parent::tearDown();
    }

    public function testListAndDeleteAsAdmin(): void
    {
        $client = static::createClient();
        $admin = static::getContainer()->get(UserRepository::class)->findOneBy(['email' => 'admin@cms.dev']);
        $client->loginUser($admin);

        $client->jsonRequest('POST', '/api/newsletter/subscribers', ['email' => 'reader@example.com']);
        self::assertResponseIsSuccessful();
        $id = json_decode($client->getResponse()->getContent(), true)['id'];

        $client->request('GET', '/api/admin/newsletter/subscribers');
        self::assertResponseIsSuccessful();
        $list = json_decode($client->getResponse()->getContent(), true);
        self::assertCount(1, $list);
        self::assertSame('reader@example.com', $list[0]['email']);

        $client->request('DELETE', "/api/admin/newsletter/subscribers/{$id}");
        self::assertResponseStatusCodeSame(204);

        $client->request('GET', '/api/admin/newsletter/subscribers');
        self::assertCount(0, json_decode($client->getResponse()->getContent(), true));
    }

    public function testListingRequiresAuthentication(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/admin/newsletter/subscribers');
        self::assertResponseStatusCodeSame(401);
    }
}
