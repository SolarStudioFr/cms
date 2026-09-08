<?php

declare(strict_types=1);

namespace Plugin\News\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260908120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add SEO/social fields (step 37) and a category (step 39) to the news plugin.';
    }

    public function isTransactional(): bool
    {
        // MySQL DDL implicitly commits; an explicit wrapping transaction just
        // triggers a deprecated "already committed" silencing path.
        return false;
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE news_article ADD seo_title VARCHAR(255) DEFAULT NULL, ADD seo_description LONGTEXT DEFAULT NULL, ADD og_image_url VARCHAR(512) DEFAULT NULL, ADD og_type VARCHAR(20) NOT NULL DEFAULT 'website', ADD canonical_url VARCHAR(512) DEFAULT NULL, ADD category_id INT DEFAULT NULL");
        $this->addSql('CREATE TABLE news_category (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(100) NOT NULL, slug VARCHAR(100) NOT NULL, UNIQUE INDEX uniq_news_category_slug (slug), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE INDEX IDX_news_article_category ON news_article (category_id)');
        $this->addSql('ALTER TABLE news_article ADD CONSTRAINT FK_news_article_category FOREIGN KEY (category_id) REFERENCES news_category (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE news_article DROP FOREIGN KEY FK_news_article_category');
        $this->addSql('DROP INDEX IDX_news_article_category ON news_article');
        $this->addSql('DROP TABLE news_category');
        $this->addSql('ALTER TABLE news_article DROP seo_title, DROP seo_description, DROP og_image_url, DROP og_type, DROP canonical_url, DROP category_id');
    }
}
