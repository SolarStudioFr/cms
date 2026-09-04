<?php

namespace App\Tests\Api;

use App\Repository\UserRepository;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Test\MailerAssertionsTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Exercises the step 24 bulk-send backend: one HTTP call per recipient via
 * send-next, resumable, ending in a "done" response and a Sent campaign.
 */
class NewsletterCampaignSendTest extends WebTestCase
{
    use MailerAssertionsTrait;

    protected function tearDown(): void
    {
        static::getContainer()->get(Connection::class)->executeStatement('DELETE FROM newsletter_campaign_send');
        static::getContainer()->get(Connection::class)->executeStatement('DELETE FROM newsletter_campaign');
        static::getContainer()->get(Connection::class)->executeStatement('DELETE FROM newsletter_subscriber');

        parent::tearDown();
    }

    public function testSendNextMailsEverySubscriberOnceThenReportsDone(): void
    {
        $client = static::createClient();
        $admin = static::getContainer()->get(UserRepository::class)->findOneBy(['email' => 'admin@cms.dev']);
        $client->loginUser($admin);

        foreach (['a@example.com', 'b@example.com'] as $email) {
            $client->jsonRequest('POST', '/api/newsletter/subscribers', ['email' => $email]);
        }

        $client->jsonRequest('POST', '/api/admin/newsletter/campaigns', [
            'subject' => 'Bulletin',
            'content' => '<p>Contenu du bulletin.</p>',
        ]);
        $campaignId = json_decode($client->getResponse()->getContent(), true)['id'];

        // Note: framework.test's ResetListener resets mailer.message_logger_listener
        // on kernel.terminate after *every* request, so mailer assertions have to be
        // made right after the request that actually sent the mail, not accumulated
        // across the whole loop.
        $client->request('POST', "/api/admin/newsletter/campaigns/{$campaignId}/send-next");
        self::assertResponseIsSuccessful();
        $first = json_decode($client->getResponse()->getContent(), true);
        self::assertFalse($first['done']);
        self::assertSame(1, $first['sentCount']);
        self::assertSame(2, $first['total']);
        self::assertEmailCount(1);
        self::assertEmailAddressContains(self::getMailerMessage(), 'To', 'a@example.com');

        $client->request('POST', "/api/admin/newsletter/campaigns/{$campaignId}/send-next");
        $second = json_decode($client->getResponse()->getContent(), true);
        self::assertFalse($second['done']);
        self::assertSame(2, $second['sentCount']);
        self::assertEmailCount(1);
        self::assertEmailAddressContains(self::getMailerMessage(), 'To', 'b@example.com');

        $client->request('POST', "/api/admin/newsletter/campaigns/{$campaignId}/send-next");
        $third = json_decode($client->getResponse()->getContent(), true);
        self::assertTrue($third['done']);
        self::assertSame(2, $third['sentCount']);
        self::assertEmailCount(0);

        // Calling again after completion is a safe no-op, not a re-send.
        $client->request('POST', "/api/admin/newsletter/campaigns/{$campaignId}/send-next");
        self::assertTrue(json_decode($client->getResponse()->getContent(), true)['done']);
        self::assertEmailCount(0);

        $client->request('GET', "/api/admin/newsletter/campaigns/{$campaignId}");
        $campaign = json_decode($client->getResponse()->getContent(), true);
        self::assertSame('sent', $campaign['status']);
        self::assertNotNull($campaign['sentAt']);
    }

    public function testSendNextRequiresAuthentication(): void
    {
        $client = static::createClient();
        $client->request('POST', '/api/admin/newsletter/campaigns/1/send-next');
        self::assertResponseStatusCodeSame(401);
    }
}
