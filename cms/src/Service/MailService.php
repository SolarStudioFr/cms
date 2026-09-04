<?php

namespace App\Service;

use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

/**
 * Generic, reusable email sending service (step 22) sitting on top of
 * Symfony Mailer. Used directly by any feature that needs to notify a user
 * by email (registration/verification - step 26, newsletter campaigns -
 * step 23/24, SMTP test button - step 29) instead of each one building its
 * own Email object.
 */
class MailService
{
    public function __construct(
        private readonly MailerInterface $mailer,
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

        $this->mailer->send($email);
    }
}
