<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250912100726 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP SEQUENCE zoo_bones_id_seq CASCADE');
        $this->addSql('CREATE SEQUENCE zoo_bone_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE zoo_teeth (id BIGINT NOT NULL, connected BOOLEAN NOT NULL, side CHAR(1) DEFAULT NULL, notes VARCHAR(255) DEFAULT NULL, stratigraphic_unit_id BIGINT NOT NULL, voc_taxonomy_id SMALLINT DEFAULT NULL, voc_tooth_id SMALLINT DEFAULT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX IDX_4CE574B1A502ADE ON zoo_teeth (stratigraphic_unit_id)');
        $this->addSql('CREATE INDEX IDX_4CE574B12A3A1291 ON zoo_teeth (voc_taxonomy_id)');
        $this->addSql('CREATE INDEX IDX_4CE574B123332A79 ON zoo_teeth (voc_tooth_id)');
        $this->addSql('COMMENT ON COLUMN zoo_teeth.side IS \'L = left, R = right, ? = indeterminate\'');
        $this->addSql('ALTER TABLE zoo_teeth ADD CONSTRAINT FK_4CE574B1A502ADE FOREIGN KEY (stratigraphic_unit_id) REFERENCES sus (id) ON DELETE RESTRICT NOT DEFERRABLE');
        $this->addSql('ALTER TABLE zoo_teeth ADD CONSTRAINT FK_4CE574B12A3A1291 FOREIGN KEY (voc_taxonomy_id) REFERENCES vocabulary.zoo_taxonomy (id) ON DELETE RESTRICT NOT DEFERRABLE');
        $this->addSql('ALTER TABLE zoo_teeth ADD CONSTRAINT FK_4CE574B123332A79 FOREIGN KEY (voc_tooth_id) REFERENCES vocabulary.zoo_bones (id) ON DELETE RESTRICT NOT DEFERRABLE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP SEQUENCE zoo_bone_id_seq CASCADE');
        $this->addSql('CREATE SEQUENCE zoo_bones_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('ALTER TABLE zoo_teeth DROP CONSTRAINT FK_4CE574B1A502ADE');
        $this->addSql('ALTER TABLE zoo_teeth DROP CONSTRAINT FK_4CE574B12A3A1291');
        $this->addSql('ALTER TABLE zoo_teeth DROP CONSTRAINT FK_4CE574B123332A79');
        $this->addSql('DROP TABLE zoo_teeth');
    }
}
