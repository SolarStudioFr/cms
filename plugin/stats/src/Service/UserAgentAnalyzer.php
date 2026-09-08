<?php

namespace Plugin\Stats\Service;

use DeviceDetector\DeviceDetector;

/**
 * Thin wrapper around matomo/device-detector: turns a raw User-Agent header
 * into the flat fields PageView stores. Chosen over a hand-rolled regex
 * table because "detailed" bot detection (step 35) is explicitly in scope -
 * this library ships a large, actively maintained bot signature database,
 * which a few in-house regexes would not keep up with.
 */
class UserAgentAnalyzer
{
    /**
     * @return array{browserName: ?string, browserVersion: ?string, osName: ?string, osVersion: ?string, deviceType: ?string, isBot: bool, botName: ?string}
     */
    public function analyze(string $userAgent): array
    {
        if ('' === trim($userAgent)) {
            return [
                'browserName' => null,
                'browserVersion' => null,
                'osName' => null,
                'osVersion' => null,
                'deviceType' => null,
                'isBot' => false,
                'botName' => null,
            ];
        }

        $detector = new DeviceDetector($userAgent);
        $detector->parse();

        if ($detector->isBot()) {
            $bot = $detector->getBot();

            return [
                'browserName' => null,
                'browserVersion' => null,
                'osName' => null,
                'osVersion' => null,
                'deviceType' => 'bot',
                'isBot' => true,
                'botName' => $bot['name'] ?? null,
            ];
        }

        $client = $detector->getClient();
        $os = $detector->getOs();

        return [
            'browserName' => $client['name'] ?? null,
            'browserVersion' => $client['version'] ?? null,
            'osName' => $os['name'] ?? null,
            'osVersion' => $os['version'] ?? null,
            'deviceType' => $detector->getDeviceName() ?: null,
            'isBot' => false,
            'botName' => null,
        ];
    }
}
