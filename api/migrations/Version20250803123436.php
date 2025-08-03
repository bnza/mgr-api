<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250803123436 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SEQUENCE vocabulary.context_types_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE vocabulary.context_types (id SMALLINT NOT NULL, type_group VARCHAR(255) NOT NULL, value VARCHAR(255) NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_C753887663CCC3321D775834 ON vocabulary.context_types (type_group, value)');
        $this->addSql('DROP INDEX uniq_ac51ceb5f6bd16468cde57295e237e06');
        $this->addSql('ALTER TABLE contexts ADD type_id SMALLINT DEFAULT NULL');
        $this->addSql('ALTER TABLE contexts DROP type');
        $this->addSql('ALTER TABLE contexts ADD CONSTRAINT FK_AC51CEB5C54C8C93 FOREIGN KEY (type_id) REFERENCES vocabulary.context_types (id) ON DELETE RESTRICT NOT DEFERRABLE');
        $this->addSql('CREATE INDEX IDX_AC51CEB5C54C8C93 ON contexts (type_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_AC51CEB5F6BD1646C54C8C935E237E06 ON contexts (site_id, type_id, name)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP SEQUENCE vocabulary.context_types_id_seq CASCADE');
        $this->addSql('DROP TABLE vocabulary.context_types');
        $this->addSql('ALTER TABLE contexts DROP CONSTRAINT FK_AC51CEB5C54C8C93');
        $this->addSql('DROP INDEX IDX_AC51CEB5C54C8C93');
        $this->addSql('DROP INDEX UNIQ_AC51CEB5F6BD1646C54C8C935E237E06');
        $this->addSql('ALTER TABLE contexts ADD type SMALLINT NOT NULL');
        $this->addSql('ALTER TABLE contexts DROP type_id');
        $this->addSql('CREATE UNIQUE INDEX uniq_ac51ceb5f6bd16468cde57295e237e06 ON contexts (site_id, type, name)');
    }
}
