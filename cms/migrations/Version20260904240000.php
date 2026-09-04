<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260904240000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create menu table (step 32: admin-managed menus attached to template hooks).';
    }

    public function isTransactional(): bool
    {
        // MySQL DDL implicitly commits; an explicit wrapping transaction just
        // triggers a deprecated "already committed" silencing path.
        return false;
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE menu (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, hook_name VARCHAR(100) DEFAULT NULL, items JSON NOT NULL, created_at DATETIME NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE menu');
    }
}
