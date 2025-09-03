<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250902162612 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SEQUENCE vocabulary.zoo_bone_parts_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE zoo_bones_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE vocabulary.zoo_bones_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE vocabulary.zoo_species_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE vocabulary.zoo_bone_parts (id SMALLINT NOT NULL, code VARCHAR(255) NOT NULL, value VARCHAR(255) NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_41B0A82877153098 ON vocabulary.zoo_bone_parts (code)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_41B0A8281D775834 ON vocabulary.zoo_bone_parts (value)');
        $this->addSql('CREATE TABLE zoo_bones (id BIGINT NOT NULL, ends_preserved SMALLINT DEFAULT NULL, side CHAR(1) DEFAULT NULL, notes VARCHAR(255) DEFAULT NULL, stratigraphic_unit_id BIGINT NOT NULL, voc_species_id SMALLINT DEFAULT NULL, voc_bone_id SMALLINT DEFAULT NULL, voc_bone_part_id SMALLINT DEFAULT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX IDX_1911F3EAA502ADE ON zoo_bones (stratigraphic_unit_id)');
        $this->addSql('CREATE INDEX IDX_1911F3EAF34A953 ON zoo_bones (voc_species_id)');
        $this->addSql('CREATE INDEX IDX_1911F3EAE26518D6 ON zoo_bones (voc_bone_id)');
        $this->addSql('CREATE INDEX IDX_1911F3EA2BAA4AD9 ON zoo_bones (voc_bone_part_id)');
        $this->addSql('COMMENT ON COLUMN zoo_bones.side IS \'L = left, R = right, ? = indeterminate\'');
        $this->addSql('CREATE TABLE vocabulary.zoo_bones (id SMALLINT NOT NULL, code VARCHAR(255) NOT NULL, value VARCHAR(255) NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_88CCAECB77153098 ON vocabulary.zoo_bones (code)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_88CCAECB1D775834 ON vocabulary.zoo_bones (value)');
        $this->addSql('CREATE TABLE vocabulary.zoo_species (id SMALLINT NOT NULL, code VARCHAR(255) NOT NULL, value VARCHAR(255) NOT NULL, scientific_name VARCHAR(255) NOT NULL, class VARCHAR(255) NOT NULL, family VARCHAR(255) DEFAULT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_F91CCA6277153098 ON vocabulary.zoo_species (code)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_F91CCA621D775834 ON vocabulary.zoo_species (value)');
        $this->addSql('ALTER TABLE zoo_bones ADD CONSTRAINT FK_1911F3EAA502ADE FOREIGN KEY (stratigraphic_unit_id) REFERENCES sus (id) ON DELETE RESTRICT NOT DEFERRABLE');
        $this->addSql('ALTER TABLE zoo_bones ADD CONSTRAINT FK_1911F3EAF34A953 FOREIGN KEY (voc_species_id) REFERENCES vocabulary.zoo_species (id) ON DELETE RESTRICT NOT DEFERRABLE');
        $this->addSql('ALTER TABLE zoo_bones ADD CONSTRAINT FK_1911F3EAE26518D6 FOREIGN KEY (voc_bone_id) REFERENCES vocabulary.zoo_bones (id) ON DELETE RESTRICT NOT DEFERRABLE');
        $this->addSql('ALTER TABLE zoo_bones ADD CONSTRAINT FK_1911F3EA2BAA4AD9 FOREIGN KEY (voc_bone_part_id) REFERENCES vocabulary.zoo_bone_parts (id) ON DELETE RESTRICT NOT DEFERRABLE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP SEQUENCE vocabulary.zoo_bone_parts_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE zoo_bones_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE vocabulary.zoo_bones_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE vocabulary.zoo_species_id_seq CASCADE');
        $this->addSql('ALTER TABLE zoo_bones DROP CONSTRAINT FK_1911F3EAA502ADE');
        $this->addSql('ALTER TABLE zoo_bones DROP CONSTRAINT FK_1911F3EAF34A953');
        $this->addSql('ALTER TABLE zoo_bones DROP CONSTRAINT FK_1911F3EAE26518D6');
        $this->addSql('ALTER TABLE zoo_bones DROP CONSTRAINT FK_1911F3EA2BAA4AD9');
        $this->addSql('DROP TABLE vocabulary.zoo_bone_parts');
        $this->addSql('DROP TABLE zoo_bones');
        $this->addSql('DROP TABLE vocabulary.zoo_bones');
        $this->addSql('DROP TABLE vocabulary.zoo_species');
    }
}
