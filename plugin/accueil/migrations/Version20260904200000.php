<?php

declare(strict_types=1);

namespace Plugin\Accueil\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260904200000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create home_content table (singleton) for the Accueil plugin.';
    }

    public function isTransactional(): bool
    {
        // MySQL DDL implicitly commits; an explicit wrapping transaction just
        // triggers a deprecated "already committed" silencing path.
        return false;
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE home_content (id INT AUTO_INCREMENT NOT NULL, content LONGTEXT NOT NULL, builder_data LONGTEXT DEFAULT NULL, updated_at DATETIME NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE home_content');
    }
}
