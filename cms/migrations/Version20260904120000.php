<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260904120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create file table for the generic file storage backend (step 01).';
    }

    public function isTransactional(): bool
    {
        // MySQL DDL implicitly commits; an explicit wrapping transaction just
        // triggers a deprecated "already committed" silencing path.
        return false;
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE file (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, uniqid VARCHAR(32) NOT NULL, slug VARCHAR(255) NOT NULL, size INT NOT NULL, width INT DEFAULT NULL, height INT DEFAULT NULL, source VARCHAR(512) DEFAULT NULL, file VARCHAR(512) NOT NULL, thumbnail JSON NOT NULL, type VARCHAR(10) NOT NULL, created_at DATETIME NOT NULL, modified_at DATETIME NOT NULL, UNIQUE INDEX uniq_file_uniqid (uniqid), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE file');
    }
}
