<?php

namespace App\Tests\Api;

use App\Repository\UserRepository;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class NewsletterCampaignAdminApiTest extends WebTestCase
{
    protected function tearDown(): void
    {
        static::getContainer()->get(Connection::class)->executeStatement('DELETE FROM newsletter_campaign_send');
        static::getContainer()->get(Connection::class)->executeStatement('DELETE FROM newsletter_campaign');

        parent::tearDown();
    }

    public function testFullCrudLifecycleAsAdmin(): void
    {
        $client = static::createClient();
        $admin = static::getContainer()->get(UserRepository::class)->findOneBy(['email' => 'admin@cms.dev']);
        $client->loginUser($admin);

        $client->jsonRequest('POST', '/api/admin/newsletter/campaigns', [
            'subject' => 'Nos actualités de septembre',
            'content' => '<p>Bonjour !</p>',
        ]);
        self::assertResponseIsSuccessful();
        $created = json_decode($client->getResponse()->getContent(), true);
        self::assertSame('draft', $created['status']);
        self::assertSame(0, $created['totalRecipients']);
        $id = $created['id'];

        $client->request(
            'PATCH',
            "/api/admin/newsletter/campaigns/{$id}",
            [],
            [],
            ['CONTENT_TYPE' => 'application/merge-patch+json'],
            json_encode(['subject' => 'Nos actualités (édition corrigée)']),
        );
        self::assertResponseIsSuccessful();
        self::assertSame('Nos actualités (édition corrigée)', json_decode($client->getResponse()->getContent(), true)['subject']);

        $client->request('GET', '/api/admin/newsletter/campaigns');
        self::assertResponseIsSuccessful();
        self::assertCount(1, json_decode($client->getResponse()->getContent(), true));

        $client->request('DELETE', "/api/admin/newsletter/campaigns/{$id}");
        self::assertResponseStatusCodeSame(204);
    }

    public function testListingRequiresAuthentication(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/admin/newsletter/campaigns');
        self::assertResponseStatusCodeSame(401);
    }
}
