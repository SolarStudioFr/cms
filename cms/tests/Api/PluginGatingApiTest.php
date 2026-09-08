<?php

namespace App\Tests\Api;

use App\Repository\UserRepository;
use App\Service\PluginRegistry;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Plugin\Homepage\Entity\HomeContent;
use Plugin\Homepage\Repository\HomeContentRepository;
use Plugin\News\Entity\NewsArticle;
use Plugin\News\Entity\NewsArticleStatus;
use Plugin\Portfolio\Entity\PortfolioItem;
use Plugin\Portfolio\Entity\PortfolioItemStatus;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * A disabled plugin must actually disappear from the public site, not just
 * the admin sidebar (bug report: disabling "newsletter" left its public
 * signup form fully working). Covers the general fix: the public
 * GET /api/plugins/enabled endpoint, plus each public content
 * plugin's provider/processor honouring PluginRegistry::isEnabled().
 */
class PluginGatingApiTest extends WebTestCase
{
    protected function tearDown(): void
    {
        $connection = static::getContainer()->get(Connection::class);
        $connection->executeStatement('DELETE FROM plugin_state');
        $connection->executeStatement('DELETE FROM portfolio_item');
        $connection->executeStatement('DELETE FROM news_article');
        $connection->executeStatement('DELETE FROM newsletter_subscriber');
        $connection->executeStatement('DELETE FROM home_content');

        parent::tearDown();
    }

    public function testEnabledPluginsEndpointListsEveryPluginByDefault(): void
    {
        $client = static::createClient();

        $client->request('GET', '/api/plugins/enabled');

        self::assertResponseIsSuccessful();
        $names = json_decode($client->getResponse()->getContent(), true);
        self::assertContains('portfolio', $names);
        self::assertContains('news', $names);
        self::assertContains('newsletter', $names);
        self::assertContains('homepage', $names);
    }

    public function testDisablingAPluginRemovesItFromTheEnabledList(): void
    {
        $client = static::createClient();
        static::getContainer()->get(PluginRegistry::class)->setEnabled('newsletter', false);

        $client->request('GET', '/api/plugins/enabled');

        self::assertNotContains('newsletter', json_decode($client->getResponse()->getContent(), true));
    }

    public function testDisabledPortfolioHidesPublishedItemsFromThePublicApi(): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $item = (new PortfolioItem())->setTitle('Item')->setContent('Content')->setStatus(PortfolioItemStatus::Published);
        $em->persist($item);
        $em->flush();
        $id = $item->getId();

        static::getContainer()->get(PluginRegistry::class)->setEnabled('portfolio', false);

        $client->request('GET', '/api/portfolio');
        self::assertResponseIsSuccessful();
        self::assertSame([], json_decode($client->getResponse()->getContent(), true));

        $client->request('GET', "/api/portfolio/{$id}");
        self::assertResponseStatusCodeSame(404);
    }

    public function testDisabledNewsHidesPublishedArticlesFromThePublicApi(): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $article = (new NewsArticle())->setTitle('Article')->setContent('Content')->setStatus(NewsArticleStatus::Published);
        $em->persist($article);
        $em->flush();
        $id = $article->getId();

        static::getContainer()->get(PluginRegistry::class)->setEnabled('news', false);

        $client->request('GET', '/api/news');
        self::assertResponseIsSuccessful();
        self::assertSame([], json_decode($client->getResponse()->getContent(), true));

        $client->request('GET', "/api/news/{$id}");
        self::assertResponseStatusCodeSame(404);
    }

    public function testDisabledNewsletterRejectsPublicSignup(): void
    {
        $client = static::createClient();
        static::getContainer()->get(PluginRegistry::class)->setEnabled('newsletter', false);

        $client->jsonRequest('POST', '/api/newsletter/subscribers', ['email' => 'visitor@example.com']);

        self::assertResponseStatusCodeSame(404);
    }

    public function testDisabledHomepageHidesConfiguredContentPubliclyButNotInAdmin(): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $home = static::getContainer()->get(HomeContentRepository::class)->findSingleton() ?? new HomeContent();
        $home->setContent('<p>Real homepage content</p>');
        $em->persist($home);
        $em->flush();

        static::getContainer()->get(PluginRegistry::class)->setEnabled('homepage', false);

        $client->request('GET', '/api/homepage');
        self::assertResponseIsSuccessful();
        self::assertSame('', json_decode($client->getResponse()->getContent(), true)['content']);

        $admin = static::getContainer()->get(UserRepository::class)->findOneBy(['email' => 'admin@cms.dev']);
        $client->loginUser($admin);
        $client->request('GET', '/api/admin/homepage');
        self::assertResponseIsSuccessful();
        self::assertSame('<p>Real homepage content</p>', json_decode($client->getResponse()->getContent(), true)['content']);
    }
}
