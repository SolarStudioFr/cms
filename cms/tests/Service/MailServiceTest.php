<?php

namespace App\Tests\Service;

use App\Service\MailService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Bundle\FrameworkBundle\Test\MailerAssertionsTrait;

class MailServiceTest extends KernelTestCase
{
    use MailerAssertionsTrait;

    public function testSendBuildsAndSendsAnHtmlEmail(): void
    {
        self::bootKernel();

        self::getContainer()->get(MailService::class)->send(
            'someone@example.com',
            'Hello',
            '<p>Test <strong>body</strong>.</p>',
        );

        self::assertEmailCount(1);
        $email = self::getMailerMessage(0);
        self::assertNotNull($email);
        self::assertEmailAddressContains($email, 'To', 'someone@example.com');
        self::assertEmailSubjectContains($email, 'Hello');
        self::assertEmailHtmlBodyContains($email, 'Test <strong>body</strong>.');
    }

    public function testReplyToIsSetWhenProvided(): void
    {
        self::bootKernel();

        self::getContainer()->get(MailService::class)->send(
            'someone@example.com',
            'Hello',
            '<p>Body</p>',
            'reply@example.com',
        );

        $email = self::getMailerMessage(0);
        self::assertNotNull($email);
        self::assertEmailAddressContains($email, 'Reply-To', 'reply@example.com');
    }
}
