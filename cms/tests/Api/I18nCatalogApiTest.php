<?php

namespace App\Tests\Api;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Functional test of the standard-Symfony translation catalog endpoint
 * (step 53): a PO file under a scanned translations path is served back as
 * a flat JSON key/value map for the requested (domain, locale) pair.
 */
class I18nCatalogApiTest extends WebTestCase
{
    public function testServesTheCatalogForAKnownDomainAndLocale(): void
    {
        $client = static::createClient();

        $client->request('GET', '/api/i18n/smoke/fr');
        self::assertResponseIsSuccessful();
        self::assertSame(['hello' => 'Bonjour'], json_decode($client->getResponse()->getContent(), true));

        $client->request('GET', '/api/i18n/smoke/en');
        self::assertResponseIsSuccessful();
        self::assertSame(['hello' => 'Hello'], json_decode($client->getResponse()->getContent(), true));
    }

    public function testReturnsAnEmptyCatalogForAnUnknownDomain(): void
    {
        $client = static::createClient();

        $client->request('GET', '/api/i18n/does_not_exist/fr');
        self::assertResponseIsSuccessful();
        self::assertSame([], json_decode($client->getResponse()->getContent(), true));
    }

    public function testAnonymousCanAccess(): void
    {
        $client = static::createClient();

        $client->request('GET', '/api/i18n/smoke/fr');
        self::assertResponseIsSuccessful();
    }
}
