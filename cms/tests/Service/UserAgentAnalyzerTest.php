<?php

namespace App\Tests\Service;

use PHPUnit\Framework\TestCase;
use Plugin\Stats\Service\UserAgentAnalyzer;

class UserAgentAnalyzerTest extends TestCase
{
    public function testParsesARegularBrowserUserAgent(): void
    {
        $analyzer = new UserAgentAnalyzer();

        $info = $analyzer->analyze(
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        );

        self::assertSame('Chrome', $info['browserName']);
        self::assertSame('Windows', $info['osName']);
        self::assertFalse($info['isBot']);
        self::assertNull($info['botName']);
    }

    public function testDetectsAKnownCrawler(): void
    {
        $analyzer = new UserAgentAnalyzer();

        $info = $analyzer->analyze('Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)');

        self::assertTrue($info['isBot']);
        self::assertSame('Googlebot', $info['botName']);
        self::assertSame('bot', $info['deviceType']);
    }

    public function testEmptyUserAgentYieldsAllNullsWithoutThrowing(): void
    {
        $analyzer = new UserAgentAnalyzer();

        $info = $analyzer->analyze('');

        self::assertFalse($info['isBot']);
        self::assertNull($info['browserName']);
        self::assertNull($info['osName']);
    }
}
