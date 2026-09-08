<?php

namespace Plugin\Stats\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Resolves a visitor's country (ISO 3166-1 alpha-2) from their IP address -
 * step 35. Uses the free, key-less ip-api.com lookup rather than a bundled
 * MaxMind GeoLite2 database: that database now requires a (free but
 * account-gated) license key to download, which isn't available in this
 * environment, while ip-api.com needs nothing and is a single HTTP call.
 * Traffic is low enough here that its rate limit (~45 req/min) is not a
 * practical concern; if it ever became one, swapping in a local .mmdb file
 * behind this same interface would need no caller changes.
 *
 * Never persists the raw IP - only the resolved country ever reaches
 * PageView - and never makes the HTTP call at all for a private/local IP
 * (always true in dev/test), so this stays a no-op, offline path in
 * PHPUnit and local development.
 */
class GeoIpResolver
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
    ) {
    }

    /** @return string|null two-letter country code, or null if unresolvable/private/lookup failed */
    public function resolveCountry(string $ip): ?string
    {
        if ('' === $ip || $this->isPrivateOrReserved($ip)) {
            return null;
        }

        try {
            $response = $this->httpClient->request('GET', "http://ip-api.com/json/{$ip}", [
                'query' => ['fields' => 'status,countryCode'],
                'timeout' => 2,
            ]);

            $data = $response->toArray(false);

            if ('success' === ($data['status'] ?? null)) {
                return $data['countryCode'] ?? null;
            }
        } catch (\Throwable) {
            // Network failure, timeout, or rate limiting: tracking must never
            // break because geolocation is unavailable - just skip the country.
        }

        return null;
    }

    private function isPrivateOrReserved(string $ip): bool
    {
        return false === filter_var($ip, \FILTER_VALIDATE_IP, \FILTER_FLAG_NO_PRIV_RANGE | \FILTER_FLAG_NO_RES_RANGE);
    }
}
