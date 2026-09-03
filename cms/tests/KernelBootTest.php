<?php

namespace App\Tests;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class KernelBootTest extends KernelTestCase
{
    public function testBootBuildsTheContainer(): void
    {
        self::bootKernel();

        self::assertTrue(self::getContainer()->has('kernel'));
    }

    public function testCacheDirIsKeptOutsideCms(): void
    {
        $kernel = self::bootKernel();

        $projectDir = $kernel->getProjectDir();
        $expected = \dirname($projectDir).'/var/cache/'.$kernel->getEnvironment();

        self::assertSame($expected, $kernel->getCacheDir());
        self::assertStringEndsNotWith('cms/var/cache/'.$kernel->getEnvironment(), $kernel->getCacheDir());
    }

    public function testLogDirIsKeptOutsideCms(): void
    {
        $kernel = self::bootKernel();

        $projectDir = $kernel->getProjectDir();
        $expected = \dirname($projectDir).'/var/log';

        self::assertSame($expected, $kernel->getLogDir());
        self::assertStringEndsNotWith('cms/var/log', $kernel->getLogDir());
    }
}
