<?php

namespace App\Tests\Api;

use App\Repository\UserRepository;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Plugin\Stats\Entity\ClickEvent;
use Plugin\Stats\Entity\PageView;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Covers the admin summary endpoint (step 36) - seeds a handful of PageView/
 * ClickEvent rows directly through Doctrine (the tracking endpoints
 * themselves are already covered by StatsTrackingApiTest) and asserts the
 * aggregates StatsDashboardController::summary computes from them.
 */
class StatsDashboardApiTest extends WebTestCase
{
    protected function tearDown(): void
    {
        $connection = static::getContainer()->get(Connection::class);
        $connection->executeStatement('DELETE FROM stats_click_event');
        $connection->executeStatement('DELETE FROM stats_page_view');

        parent::tearDown();
    }

    private function seedPageView(EntityManagerInterface $em, string $url, string $browser, ?float $time, ?int $scroll, bool $isBot = false): PageView
    {
        $pageView = new PageView();
        $pageView->setUrl($url);
        $pageView->setBrowserName($browser);
        $pageView->setTimeOnPageSeconds($time);
        $pageView->setMaxScrollPercent($scroll);
        $pageView->setIsBot($isBot);
        if ($isBot) {
            $pageView->setBotName('Googlebot');
        }
        $em->persist($pageView);

        return $pageView;
    }

    public function testSummaryAggregatesSeededData(): void
    {
        $client = static::createClient();
        $admin = static::getContainer()->get(UserRepository::class)->findOneBy(['email' => 'admin@cms.dev']);
        $client->loginUser($admin);

        $em = static::getContainer()->get(EntityManagerInterface::class);
        $this->seedPageView($em, '/', 'Chrome', 10.0, 50);
        $this->seedPageView($em, '/', 'Chrome', 20.0, 100);
        $this->seedPageView($em, '/portfolio', 'Firefox', null, null);
        $this->seedPageView($em, '/', 'crawler', null, null, isBot: true);
        $em->flush();

        $click = new ClickEvent();
        $click->setPageView($this->seedPageView($em, '/contact', 'Chrome', 5.0, 10));
        $click->setElementType('link');
        $click->setLabel('Nous contacter');
        $click->setTargetUrl('/contact');
        $em->persist($click);
        $em->flush();

        $client->request('GET', '/api/admin/stats/summary');
        self::assertResponseIsSuccessful();
        $summary = json_decode($client->getResponse()->getContent(), true);

        self::assertSame(5, $summary['totalPageViews']);
        // Only 3 of the 5 seeded rows have a non-null timeOnPageSeconds (10, 20, 5).
        self::assertEqualsWithDelta(35 / 3, $summary['avgTimeOnPageSeconds'], 0.001);
        self::assertSame(['bot' => 1, 'human' => 4], $summary['botVsHuman']);

        $topPage = $summary['topPages'][0];
        self::assertSame('/', $topPage['url']);
        self::assertSame(3, $topPage['views']);

        self::assertSame([['name' => 'Googlebot', 'count' => 1]], $summary['topBots']);

        self::assertCount(1, $summary['topClicks']);
        self::assertSame('link', $summary['topClicks'][0]['elementType']);
        self::assertSame(1, $summary['topClicks'][0]['count']);
    }

    public function testAnonymousCannotAccessTheDashboard(): void
    {
        $client = static::createClient();

        $client->request('GET', '/api/admin/stats/summary');

        self::assertResponseStatusCodeSame(401);
    }
}
