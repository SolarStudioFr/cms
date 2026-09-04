<?php

namespace App\Service;

use App\Repository\SiteConfigRepository;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mime\Email;

/**
 * Generic, reusable email sending service (step 22) sitting on top of
 * Symfony Mailer. Used directly by any feature that needs to notify a user
 * by email (registration/verification - step 26, newsletter campaigns -
 * step 23/24, SMTP test button - step 29).
 *
 * Step 29 added the ability to override the transport with SMTP settings
 * stored in SiteConfig (admin-editable) instead of always using the
 * compile-time MAILER_DSN env var - Symfony's own MailerInterface is built
 * once from that env var at container compile time, so overriding it at
 * runtime means building our own Transport/Mailer per send instead of
 * injecting MailerInterface directly.
 */
class MailService
{
    public function __construct(
        private readonly SiteConfigRepository $siteConfigRepository,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly string $fallbackDsn,
        private readonly string $fromAddress,
        private readonly string $fromName,
    ) {
    }

    /**
     * Sends a single HTML email. Kept deliberately simple (no attachments,
     * no templating engine) - callers build their own HTML body, since the
     * bodies needed so far (verification link, newsletter campaign, test
     * email) are all short and don't warrant pulling in Twig rendering here.
     */
    public function send(string $to, string $subject, string $htmlBody, ?string $replyTo = null): void
    {
        $email = (new Email())
            ->from(sprintf('%s <%s>', $this->fromName, $this->fromAddress))
            ->to($to)
            ->subject($subject)
            ->html($htmlBody);

        if (null !== $replyTo) {
            $email->replyTo($replyTo);
        }

        $mailer = new Mailer(Transport::fromDsn($this->resolveDsn(), $this->eventDispatcher));
        $mailer->send($email);
    }

    /**
     * SiteConfig's stored SMTP host wins when set (step 29's admin
     * settings); otherwise falls back to the env-configured MAILER_DSN
     * (Mailpit in dev - step 22). Encryption is left to Symfony's smtp
     * transport, which infers TLS from the port (465/587) - SiteConfig's
     * `smtpEncryption` field is display-only, see its docblock.
     */
    private function resolveDsn(): string
    {
        $config = $this->siteConfigRepository->findOrCreate();

        if (null === $config->getSmtpHost() || '' === $config->getSmtpHost()) {
            return $this->fallbackDsn;
        }

        $auth = '';
        if (null !== $config->getSmtpUser() || null !== $config->getSmtpPassword()) {
            $auth = sprintf('%s:%s@', rawurlencode($config->getSmtpUser() ?? ''), rawurlencode($config->getSmtpPassword() ?? ''));
        }

        return sprintf('smtp://%s%s:%d', $auth, $config->getSmtpHost(), $config->getSmtpPort() ?? 25);
    }
}
