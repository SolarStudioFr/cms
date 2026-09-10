<?php

declare(strict_types=1);

namespace Plugin\Page\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260904170000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add nullable builder_data column to page for the builder integration (step 16).';
    }

    public function isTransactional(): bool
    {
        // MySQL DDL implicitly commits; an explicit wrapping transaction just
        // triggers a deprecated "already committed" silencing path.
        return false;
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE page ADD builder_data LONGTEXT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE page DROP builder_data');
    }
}
