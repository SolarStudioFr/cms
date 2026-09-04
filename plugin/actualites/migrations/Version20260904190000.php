<?php

declare(strict_types=1);

namespace Plugin\Actualites\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260904190000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create actualite table for the Actualites plugin.';
    }

    public function isTransactional(): bool
    {
        // MySQL DDL implicitly commits; an explicit wrapping transaction just
        // triggers a deprecated "already committed" silencing path.
        return false;
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE actualite (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(255) NOT NULL, content LONGTEXT NOT NULL, builder_data LONGTEXT DEFAULT NULL, status VARCHAR(20) NOT NULL, slug VARCHAR(255) NOT NULL, cover_image_url VARCHAR(512) DEFAULT NULL, cover_image_alt VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL, UNIQUE INDEX uniq_actualite_slug (slug), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE actualite');
    }
}
