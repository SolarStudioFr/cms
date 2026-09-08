<?php

namespace App\Tests\Api;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Plugin\Stats\Entity\PageView;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Covers the public, unauthenticated tracking endpoints (steps 34-35):
 * creating a page view, updating it via a heartbeat, and recording clicks.
 * Requests in this suite always come from 127.0.0.1, so GeoIpResolver never
 * makes a real network call (see GeoIpResolverTest for that behaviour in
 * isolation) - country is expected to stay null throughout.
 */
class StatsTrackingApiTest extends WebTestCase
{
    protected function tearDown(): void
    {
        $connection = static::getContainer()->get(Connection::class);
        $connection->executeStatement('DELETE FROM stats_click_event');
        $connection->executeStatement('DELETE FROM stats_page_view');

        parent::tearDown();
    }

    public function testCreatingAPageViewParsesTheUserAgentServerSide(): void
    {
        $client = static::createClient();

        $client->request(
            'POST',
            '/api/stats/pageview',
            server: ['HTTP_USER_AGENT' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36'],
            content: json_encode(['url' => '/portfolio/mon-projet', 'referrer' => 'https://google.com', 'screenWidth' => 1920, 'screenHeight' => 1080]),
        );

        self::assertResponseStatusCodeSame(201);
        $id = json_decode($client->getResponse()->getContent(), true)['id'];
        self::assertIsInt($id);

        $pageView = static::getContainer()->get(EntityManagerInterface::class)->getRepository(PageView::class)->find($id);
        self::assertSame('/portfolio/mon-projet', $pageView->getUrl());
        self::assertSame('https://google.com', $pageView->getReferrer());
        self::assertSame(1920, $pageView->getScreenWidth());
        self::assertSame('Chrome', $pageView->getBrowserName());
        self::assertSame('Windows', $pageView->getOsName());
        self::assertFalse($pageView->isBot());
        self::assertNull($pageView->getCountry());
    }

    public function testCreatingAPageViewWithABotUserAgentFlagsIt(): void
    {
        $client = static::createClient();

        $client->request(
            'POST',
            '/api/stats/pageview',
            server: ['HTTP_USER_AGENT' => 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)'],
            content: json_encode(['url' => '/']),
        );

        self::assertResponseStatusCodeSame(201);
        $id = json_decode($client->getResponse()->getContent(), true)['id'];

        $pageView = static::getContainer()->get(EntityManagerInterface::class)->getRepository(PageView::class)->find($id);
        self::assertTrue($pageView->isBot());
        self::assertSame('Googlebot', $pageView->getBotName());
    }

    public function testHeartbeatMergesInTheHighestValuesSeen(): void
    {
        $client = static::createClient();

        $client->request('POST', '/api/stats/pageview', content: json_encode(['url' => '/']));
        $id = json_decode($client->getResponse()->getContent(), true)['id'];

        $client->request('POST', "/api/stats/pageview/{$id}/heartbeat", content: json_encode(['timeOnPageSeconds' => 5, 'maxScrollPercent' => 40]));
        self::assertResponseIsSuccessful();

        // A second, lower beacon must never overwrite the higher value already recorded.
        $client->request('POST', "/api/stats/pageview/{$id}/heartbeat", content: json_encode(['timeOnPageSeconds' => 2, 'maxScrollPercent' => 90]));
        self::assertResponseIsSuccessful();

        $pageView = static::getContainer()->get(EntityManagerInterface::class)->getRepository(PageView::class)->find($id);
        self::assertSame(5.0, $pageView->getTimeOnPageSeconds());
        self::assertSame(90, $pageView->getMaxScrollPercent());
    }

    public function testHeartbeatOnUnknownPageViewReturns404(): void
    {
        $client = static::createClient();

        $client->request('POST', '/api/stats/pageview/999999/heartbeat', content: json_encode(['timeOnPageSeconds' => 1]));

        self::assertResponseStatusCodeSame(404);
    }

    public function testClickIsRecordedAgainstItsPageView(): void
    {
        $client = static::createClient();

        $client->request('POST', '/api/stats/pageview', content: json_encode(['url' => '/']));
        $id = json_decode($client->getResponse()->getContent(), true)['id'];

        $client->request('POST', '/api/stats/click', content: json_encode([
            'pageViewId' => $id,
            'elementType' => 'link',
            'label' => 'Nous contacter',
            'targetUrl' => '/contact',
        ]));

        self::assertResponseStatusCodeSame(201);

        $count = (int) static::getContainer()->get(Connection::class)
            ->executeQuery('SELECT COUNT(*) FROM stats_click_event WHERE page_view_id = ?', [$id])
            ->fetchOne();
        self::assertSame(1, $count);
    }

    public function testClickWithAnInvalidPageViewIdIsRejected(): void
    {
        $client = static::createClient();

        $client->request('POST', '/api/stats/click', content: json_encode(['pageViewId' => 999999, 'elementType' => 'button']));

        self::assertResponseStatusCodeSame(400);
    }
}
