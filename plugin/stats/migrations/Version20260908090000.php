<?php

declare(strict_types=1);

namespace Plugin\Stats\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260908090000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create stats_page_view and stats_click_event tables for the Stats plugin (steps 34-36).';
    }

    public function isTransactional(): bool
    {
        // MySQL DDL implicitly commits; an explicit wrapping transaction just
        // triggers a deprecated "already committed" silencing path.
        return false;
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE stats_page_view (id INT AUTO_INCREMENT NOT NULL, url VARCHAR(512) NOT NULL, referrer VARCHAR(512) DEFAULT NULL, browser_name VARCHAR(100) DEFAULT NULL, browser_version VARCHAR(50) DEFAULT NULL, os_name VARCHAR(100) DEFAULT NULL, os_version VARCHAR(50) DEFAULT NULL, device_type VARCHAR(50) DEFAULT NULL, language VARCHAR(10) DEFAULT NULL, screen_width INT DEFAULT NULL, screen_height INT DEFAULT NULL, country VARCHAR(2) DEFAULT NULL, is_bot TINYINT(1) NOT NULL, bot_name VARCHAR(100) DEFAULT NULL, time_on_page_seconds DOUBLE PRECISION DEFAULT NULL, max_scroll_percent INT DEFAULT NULL, created_at DATETIME NOT NULL, INDEX idx_stats_page_view_created_at (created_at), INDEX idx_stats_page_view_url (url), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE stats_click_event (id INT AUTO_INCREMENT NOT NULL, page_view_id INT NOT NULL, element_type VARCHAR(20) NOT NULL, label VARCHAR(255) DEFAULT NULL, target_url VARCHAR(512) DEFAULT NULL, created_at DATETIME NOT NULL, INDEX idx_stats_click_event_created_at (created_at), INDEX IDX_STATS_CLICK_EVENT_PAGE_VIEW (page_view_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE stats_click_event ADD CONSTRAINT FK_STATS_CLICK_EVENT_PAGE_VIEW FOREIGN KEY (page_view_id) REFERENCES stats_page_view (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE stats_click_event DROP FOREIGN KEY FK_STATS_CLICK_EVENT_PAGE_VIEW');
        $this->addSql('DROP TABLE stats_click_event');
        $this->addSql('DROP TABLE stats_page_view');
    }
}
