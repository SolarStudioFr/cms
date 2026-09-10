<?php

declare(strict_types=1);

namespace Plugin\Newsletter\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260910200000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add Subscriber.name and Subscriber.locale (captured at public signup, step 58 follow-up): prepares per-subscriber-language sending, not built yet.';
    }

    public function isTransactional(): bool
    {
        // MySQL DDL implicitly commits; an explicit wrapping transaction just
        // triggers a deprecated "already committed" silencing path.
        return false;
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE newsletter_subscriber ADD name VARCHAR(255) DEFAULT NULL, ADD locale VARCHAR(10) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE newsletter_subscriber DROP name, DROP locale');
    }
}
