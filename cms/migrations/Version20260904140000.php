<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260904140000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create plugin_state table for the plugin manager admin (step 06).';
    }

    public function isTransactional(): bool
    {
        // MySQL DDL implicitly commits; an explicit wrapping transaction just
        // triggers a deprecated "already committed" silencing path.
        return false;
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE plugin_state (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(100) NOT NULL, enabled TINYINT(1) NOT NULL, UNIQUE INDEX uniq_plugin_state_name (name), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE plugin_state');
    }
}
