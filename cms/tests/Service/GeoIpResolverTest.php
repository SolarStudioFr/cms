<?php

namespace App\Tests\Service;

use PHPUnit\Framework\TestCase;
use Plugin\Stats\Service\GeoIpResolver;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

class GeoIpResolverTest extends TestCase
{
    public function testPrivateOrLocalIpsAreSkippedWithoutAnyHttpCall(): void
    {
        // A client that would fail the test if it were ever called - proves
        // 127.0.0.1 (what every functional/PHPUnit HTTP test hits) never
        // triggers a real network lookup.
        $httpClient = new MockHttpClient(function () {
            self::fail('No HTTP call should be made for a private/local IP.');
        });

        $resolver = new GeoIpResolver($httpClient);

        self::assertNull($resolver->resolveCountry('127.0.0.1'));
        self::assertNull($resolver->resolveCountry('192.168.1.10'));
        self::assertNull($resolver->resolveCountry('::1'));
        self::assertNull($resolver->resolveCountry(''));
    }

    public function testResolvesACountryFromASuccessfulLookup(): void
    {
        $httpClient = new MockHttpClient(
            new MockResponse(json_encode(['status' => 'success', 'countryCode' => 'FR'])),
        );

        $resolver = new GeoIpResolver($httpClient);

        self::assertSame('FR', $resolver->resolveCountry('8.8.8.8'));
    }

    public function testFailedLookupReturnsNullInsteadOfThrowing(): void
    {
        $httpClient = new MockHttpClient(
            new MockResponse(json_encode(['status' => 'fail'])),
        );

        $resolver = new GeoIpResolver($httpClient);

        self::assertNull($resolver->resolveCountry('8.8.8.8'));
    }
}
