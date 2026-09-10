<?php

declare(strict_types=1);

namespace Plugin\Page\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260908120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add SEO/social fields and a featured image to the page table (step 37).';
    }

    public function isTransactional(): bool
    {
        // MySQL DDL implicitly commits; an explicit wrapping transaction just
        // triggers a deprecated "already committed" silencing path.
        return false;
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE page ADD seo_title VARCHAR(255) DEFAULT NULL, ADD seo_description LONGTEXT DEFAULT NULL, ADD og_image_url VARCHAR(512) DEFAULT NULL, ADD og_type VARCHAR(20) NOT NULL DEFAULT 'website', ADD canonical_url VARCHAR(512) DEFAULT NULL, ADD featured_image_url VARCHAR(512) DEFAULT NULL, ADD featured_image_alt VARCHAR(255) DEFAULT NULL");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE page DROP seo_title, DROP seo_description, DROP og_image_url, DROP og_type, DROP canonical_url, DROP featured_image_url, DROP featured_image_alt');
    }
}
