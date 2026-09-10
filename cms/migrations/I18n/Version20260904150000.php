<?php

declare(strict_types=1);

namespace Plugin\I18n\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Creates the lang table for the i18n plugin (step 07) and seeds the two
 * starting languages required by MAIN.md ("FR + EN au départ").
 */
final class Version20260904150000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create lang table for the i18n plugin and seed FR/EN.';
    }

    public function isTransactional(): bool
    {
        // MySQL DDL implicitly commits; an explicit wrapping transaction just
        // triggers a deprecated "already committed" silencing path.
        return false;
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE lang (id INT AUTO_INCREMENT NOT NULL, code VARCHAR(10) NOT NULL, label VARCHAR(100) NOT NULL, active TINYINT(1) NOT NULL, UNIQUE INDEX uniq_lang_code (code), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql("INSERT INTO lang (code, label, active) VALUES ('fr', 'Français', 1), ('en', 'English', 1)");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE lang');
    }
}
