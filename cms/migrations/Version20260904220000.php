<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260904220000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add verification/registration-date columns to user (step 26).';
    }

    public function isTransactional(): bool
    {
        // MySQL DDL implicitly commits; an explicit wrapping transaction just
        // triggers a deprecated "already committed" silencing path.
        return false;
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user ADD verified TINYINT(1) NOT NULL DEFAULT 0, ADD verification_token VARCHAR(64) DEFAULT NULL, ADD verified_at DATETIME DEFAULT NULL, ADD created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP');
        $this->addSql('ALTER TABLE user ADD UNIQUE INDEX uniq_user_verification_token (verification_token)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user DROP INDEX uniq_user_verification_token');
        $this->addSql('ALTER TABLE user DROP verified, DROP verification_token, DROP verified_at, DROP created_at');
    }
}
