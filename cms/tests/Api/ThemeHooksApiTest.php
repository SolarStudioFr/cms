<?php

namespace App\Tests\Api;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ThemeHooksApiTest extends WebTestCase
{
    public function testReturnsTheHooksDeclaredByTheActiveThemeManifest(): void
    {
        $client = static::createClient();
        $admin = static::getContainer()->get(UserRepository::class)->findOneBy(['email' => 'admin@cms.dev']);
        $client->loginUser($admin);

        $client->request('GET', '/api/admin/theme/hooks');
        self::assertResponseIsSuccessful();
        $hooks = json_decode($client->getResponse()->getContent(), true);

        self::assertSame(
            [
                ['name' => 'header-menu', 'label' => "Menu d'en-tête"],
                ['name' => 'footer-menu', 'label' => 'Menu de pied de page'],
            ],
            $hooks,
        );
    }

    public function testAnonymousCannotAccess(): void
    {
        $client = static::createClient();

        $client->request('GET', '/api/admin/theme/hooks');

        self::assertResponseStatusCodeSame(401);
    }
}
