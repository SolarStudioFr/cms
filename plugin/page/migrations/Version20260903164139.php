<?php

declare(strict_types=1);

namespace Plugin\Page\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260903164139 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create page table for the Page plugin.';
    }

    public function isTransactional(): bool
    {
        // MySQL DDL implicitly commits; an explicit wrapping transaction just
        // triggers a deprecated "already committed" silencing path.
        return false;
    }

    public function up(Schema $schema): void
    {
        // Note: the diff tool also proposed an unrelated ALTER TABLE
        // rememberme_token CHAR->VARCHAR change - a false positive from
        // comparing an unmapped hand-written table; intentionally dropped.
        $this->addSql('CREATE TABLE page (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(255) NOT NULL, content LONGTEXT NOT NULL, status VARCHAR(20) NOT NULL, slug VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, UNIQUE INDEX uniq_page_slug (slug), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE page');
    }
}
