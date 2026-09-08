<?php

declare(strict_types=1);

namespace Plugin\Portfolio\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260908120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add SEO/social fields (step 37) and tags (step 38) to the portfolio plugin.';
    }

    public function isTransactional(): bool
    {
        // MySQL DDL implicitly commits; an explicit wrapping transaction just
        // triggers a deprecated "already committed" silencing path.
        return false;
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE portfolio_item ADD seo_title VARCHAR(255) DEFAULT NULL, ADD seo_description LONGTEXT DEFAULT NULL, ADD og_image_url VARCHAR(512) DEFAULT NULL, ADD og_type VARCHAR(20) NOT NULL DEFAULT 'website', ADD canonical_url VARCHAR(512) DEFAULT NULL");
        $this->addSql('CREATE TABLE portfolio_tag (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(100) NOT NULL, slug VARCHAR(100) NOT NULL, UNIQUE INDEX uniq_portfolio_tag_slug (slug), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE portfolio_item_tag (portfolio_item_id INT NOT NULL, tag_id INT NOT NULL, INDEX IDX_portfolio_item_tag_item (portfolio_item_id), INDEX IDX_portfolio_item_tag_tag (tag_id), PRIMARY KEY (portfolio_item_id, tag_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE portfolio_item_tag ADD CONSTRAINT FK_portfolio_item_tag_item FOREIGN KEY (portfolio_item_id) REFERENCES portfolio_item (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE portfolio_item_tag ADD CONSTRAINT FK_portfolio_item_tag_tag FOREIGN KEY (tag_id) REFERENCES portfolio_tag (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE portfolio_item_tag DROP FOREIGN KEY FK_portfolio_item_tag_item');
        $this->addSql('ALTER TABLE portfolio_item_tag DROP FOREIGN KEY FK_portfolio_item_tag_tag');
        $this->addSql('DROP TABLE portfolio_item_tag');
        $this->addSql('DROP TABLE portfolio_tag');
        $this->addSql('ALTER TABLE portfolio_item DROP seo_title, DROP seo_description, DROP og_image_url, DROP og_type, DROP canonical_url');
    }
}
