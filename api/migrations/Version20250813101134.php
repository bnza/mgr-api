<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250813101134 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SEQUENCE vocabulary.media_object_types_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE vocabulary.media_object_types (id SMALLINT NOT NULL, type_group VARCHAR(255) NOT NULL, value VARCHAR(255) NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_F365A0CA63CCC3321D775834 ON vocabulary.media_object_types (type_group, value)');
        $this->addSql('ALTER TABLE media_objects ADD description TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE media_objects ADD type_id SMALLINT NOT NULL');
        $this->addSql('ALTER TABLE media_objects ADD CONSTRAINT FK_D3CD4ABAC54C8C93 FOREIGN KEY (type_id) REFERENCES vocabulary.media_object_types (id) ON DELETE RESTRICT NOT DEFERRABLE');
        $this->addSql('CREATE INDEX IDX_D3CD4ABAC54C8C93 ON media_objects (type_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP SEQUENCE vocabulary.media_object_types_id_seq CASCADE');
        $this->addSql('DROP TABLE vocabulary.media_object_types');
        $this->addSql('ALTER TABLE media_objects DROP CONSTRAINT FK_D3CD4ABAC54C8C93');
        $this->addSql('DROP INDEX IDX_D3CD4ABAC54C8C93');
        $this->addSql('ALTER TABLE media_objects DROP description');
        $this->addSql('ALTER TABLE media_objects DROP type_id');
    }
}
