<?php

declare(strict_types=1);

namespace Plugin\Newsletter\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260904210000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create newsletter_subscriber, newsletter_campaign and newsletter_campaign_send tables for the Newsletter plugin.';
    }

    public function isTransactional(): bool
    {
        // MySQL DDL implicitly commits; an explicit wrapping transaction just
        // triggers a deprecated "already committed" silencing path.
        return false;
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE newsletter_subscriber (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(255) NOT NULL, subscribed_at DATETIME NOT NULL, UNIQUE INDEX uniq_newsletter_subscriber_email (email), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE newsletter_campaign (id INT AUTO_INCREMENT NOT NULL, subject VARCHAR(255) NOT NULL, content LONGTEXT NOT NULL, status VARCHAR(20) NOT NULL, total_recipients INT NOT NULL, created_at DATETIME NOT NULL, sent_at DATETIME DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE newsletter_campaign_send (id INT AUTO_INCREMENT NOT NULL, campaign_id INT NOT NULL, subscriber_id INT NOT NULL, sent_at DATETIME NOT NULL, INDEX IDX_NEWSLETTER_SEND_CAMPAIGN (campaign_id), INDEX IDX_NEWSLETTER_SEND_SUBSCRIBER (subscriber_id), UNIQUE INDEX uniq_campaign_send_pair (campaign_id, subscriber_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE newsletter_campaign_send ADD CONSTRAINT FK_NEWSLETTER_SEND_CAMPAIGN FOREIGN KEY (campaign_id) REFERENCES newsletter_campaign (id)');
        $this->addSql('ALTER TABLE newsletter_campaign_send ADD CONSTRAINT FK_NEWSLETTER_SEND_SUBSCRIBER FOREIGN KEY (subscriber_id) REFERENCES newsletter_subscriber (id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE newsletter_campaign_send DROP FOREIGN KEY FK_NEWSLETTER_SEND_CAMPAIGN');
        $this->addSql('ALTER TABLE newsletter_campaign_send DROP FOREIGN KEY FK_NEWSLETTER_SEND_SUBSCRIBER');
        $this->addSql('DROP TABLE newsletter_campaign_send');
        $this->addSql('DROP TABLE newsletter_campaign');
        $this->addSql('DROP TABLE newsletter_subscriber');
    }
}
