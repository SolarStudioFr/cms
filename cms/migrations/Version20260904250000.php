<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260904250000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create admin_menu_config table (steps 32/33 add-on: order/separators of the admin sidebar nav).';
    }

    public function isTransactional(): bool
    {
        // MySQL DDL implicitly commits; an explicit wrapping transaction just
        // triggers a deprecated "already committed" silencing path.
        return false;
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE admin_menu_config (id INT AUTO_INCREMENT NOT NULL, items JSON NOT NULL, updated_at DATETIME NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE admin_menu_config');
    }
}
