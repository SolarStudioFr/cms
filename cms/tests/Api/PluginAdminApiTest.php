<?php

namespace App\Tests\Api;

use App\Repository\UserRepository;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Functional test of the plugin manager backend (step 06): listing
 * (enabled-only vs. every plugin), enable/disable, and delete, through the
 * real HTTP layer. Uses a throwaway plugin directory for the delete test so
 * the real "page" plugin is never actually removed from disk.
 */
class PluginAdminApiTest extends WebTestCase
{
    protected function tearDown(): void
    {
        static::getContainer()->get(Connection::class)->executeStatement('DELETE FROM plugin_state');

        parent::tearDown();
    }

    public function testListingOnlyIncludesEnabledPluginsAfterDisabling(): void
    {
        $client = static::createClient();
        $admin = static::getContainer()->get(UserRepository::class)->findOneBy(['email' => 'admin@cms.dev']);
        $client->loginUser($admin);

        $client->request('GET', '/api/admin/plugins');
        self::assertResponseIsSuccessful();
        $active = json_decode($client->getResponse()->getContent(), true);
        self::assertContains('page', array_column($active, 'name'));

        $client->request('GET', '/api/admin/plugins/all');
        $all = json_decode($client->getResponse()->getContent(), true);
        $page = current(array_filter($all, static fn (array $p) => 'page' === $p['name']));
        self::assertNotFalse($page);
        self::assertTrue($page['enabled']);

        $client->jsonRequest('PATCH', '/api/admin/plugins/page', ['enabled' => false]);
        self::assertResponseIsSuccessful();

        $client->request('GET', '/api/admin/plugins');
        $active = json_decode($client->getResponse()->getContent(), true);
        self::assertNotContains('page', array_column($active, 'name'));

        $client->request('GET', '/api/admin/plugins/all');
        $all = json_decode($client->getResponse()->getContent(), true);
        $page = current(array_filter($all, static fn (array $p) => 'page' === $p['name']));
        self::assertFalse($page['enabled']);

        $client->jsonRequest('PATCH', '/api/admin/plugins/page', ['enabled' => true]);
        self::assertResponseIsSuccessful();
        $client->request('GET', '/api/admin/plugins');
        self::assertContains('page', array_column(json_decode($client->getResponse()->getContent(), true), 'name'));
    }

    public function testDeletingAPluginRemovesItsDirectory(): void
    {
        $client = static::createClient();
        $pluginDir = realpath(\dirname((string) static::getContainer()->getParameter('kernel.project_dir')).'/plugin');
        $throwawayDir = $pluginDir.'/throwaway-test-plugin';

        if (!is_dir($throwawayDir)) {
            mkdir($throwawayDir, 0777, true);
        }
        file_put_contents($throwawayDir.'/plugin.json', json_encode([
            'name' => 'throwaway-test-plugin',
            'label' => 'Throwaway',
            'remoteEntry' => '/build/plugins/throwaway-test-plugin/remoteEntry.js',
            'exposedModule' => './AdminModule',
        ]));

        try {
            $admin = static::getContainer()->get(UserRepository::class)->findOneBy(['email' => 'admin@cms.dev']);
            $client->loginUser($admin);

            $client->request('GET', '/api/admin/plugins/all');
            self::assertContains('throwaway-test-plugin', array_column(json_decode($client->getResponse()->getContent(), true), 'name'));

            $client->request('DELETE', '/api/admin/plugins/throwaway-test-plugin');
            self::assertResponseStatusCodeSame(204);

            self::assertDirectoryDoesNotExist($throwawayDir);

            $client->request('DELETE', '/api/admin/plugins/throwaway-test-plugin');
            self::assertResponseStatusCodeSame(404);
        } finally {
            if (is_dir($throwawayDir)) {
                unlink($throwawayDir.'/plugin.json');
                rmdir($throwawayDir);
            }
        }
    }

    public function testAnonymousCannotAccess(): void
    {
        $client = static::createClient();

        $client->request('GET', '/api/admin/plugins/all');
        self::assertResponseStatusCodeSame(401);
    }
}
