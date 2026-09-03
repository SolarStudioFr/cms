<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class HomeAndAdminShellTest extends WebTestCase
{
    public function testHomeRendersThePublicSpaShell(): void
    {
        $client = static::createClient();

        $client->request('GET', '/');

        self::assertResponseIsSuccessful();
        self::assertSelectorExists('#app');
    }

    public function testAdminRendersTheAdminSpaShell(): void
    {
        $client = static::createClient();

        $client->request('GET', '/adm');

        self::assertResponseIsSuccessful();
        self::assertSelectorExists('#adm');
    }

    public function testApiRoutesAreNotShadowedByTheCatchAllRoutes(): void
    {
        $client = static::createClient();

        $client->request('GET', '/api/pages');

        self::assertResponseIsSuccessful();
    }
}
