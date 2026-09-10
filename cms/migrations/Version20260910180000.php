<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260910180000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add SiteConfig.defaultLocale (step 55 correction): a default interface locale, selected in Configuration.';
    }

    public function isTransactional(): bool
    {
        // MySQL DDL implicitly commits; an explicit wrapping transaction just
        // triggers a deprecated "already committed" silencing path.
        return false;
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE site_config ADD default_locale VARCHAR(10) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE site_config DROP default_locale');
    }
}
