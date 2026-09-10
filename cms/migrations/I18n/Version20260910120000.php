<?php

declare(strict_types=1);

namespace Plugin\I18n\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260910120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Drop the translation table (step 53): interface strings move to standard Symfony translation files under cms/translations/, loaded via TranslatorInterface instead of this generic key/value store.';
    }

    public function isTransactional(): bool
    {
        // MySQL DDL implicitly commits; an explicit wrapping transaction just
        // triggers a deprecated "already committed" silencing path.
        return false;
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE translation DROP FOREIGN KEY FK_TRANSLATION_LANG');
        $this->addSql('DROP TABLE translation');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('CREATE TABLE translation (id INT AUTO_INCREMENT NOT NULL, lang_id INT NOT NULL, domain VARCHAR(100) NOT NULL, message_key VARCHAR(255) NOT NULL, value LONGTEXT NOT NULL, INDEX IDX_TRANSLATION_LANG (lang_id), UNIQUE INDEX uniq_translation_lang_domain_key (lang_id, domain, message_key), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE translation ADD CONSTRAINT FK_TRANSLATION_LANG FOREIGN KEY (lang_id) REFERENCES lang (id)');
    }
}
