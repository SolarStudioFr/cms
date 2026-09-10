<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260910190000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create content_translation table (step 58): the generic content-language overlay (entityType/entityId/field/locale/value).';
    }

    public function isTransactional(): bool
    {
        // MySQL DDL implicitly commits; an explicit wrapping transaction just
        // triggers a deprecated "already committed" silencing path.
        return false;
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE content_translation (id INT AUTO_INCREMENT NOT NULL, entity_type VARCHAR(50) NOT NULL, entity_id INT NOT NULL, field VARCHAR(50) NOT NULL, locale VARCHAR(10) NOT NULL, value LONGTEXT NOT NULL, UNIQUE INDEX uniq_content_translation_key (entity_type, entity_id, field, locale), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE content_translation');
    }
}
