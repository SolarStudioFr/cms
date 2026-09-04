<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260904230000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create site_config table (steps 29-31: general identity + SMTP settings).';
    }

    public function isTransactional(): bool
    {
        // MySQL DDL implicitly commits; an explicit wrapping transaction just
        // triggers a deprecated "already committed" silencing path.
        return false;
    }

    public function up(Schema $schema): void
    {
        $this->addSql("CREATE TABLE site_config (id INT AUTO_INCREMENT NOT NULL, site_name VARCHAR(255) NOT NULL DEFAULT 'Solar CMS', logo_url VARCHAR(255) DEFAULT NULL, favicon_url VARCHAR(255) DEFAULT NULL, smtp_host VARCHAR(255) DEFAULT NULL, smtp_port INT DEFAULT NULL, smtp_user VARCHAR(255) DEFAULT NULL, smtp_password VARCHAR(255) DEFAULT NULL, smtp_encryption VARCHAR(20) DEFAULT NULL, updated_at DATETIME NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE site_config');
    }
}
