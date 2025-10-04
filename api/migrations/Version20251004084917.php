<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251004084917 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SEQUENCE vocabulary.botany_element_parts_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE vocabulary.botany_elements_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE botany_seed_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE vocabulary.botany_taxonomy_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE vocabulary.botany_element_parts (id SMALLINT NOT NULL, value VARCHAR(255) NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_4371DD621D775834 ON vocabulary.botany_element_parts (value)');
        $this->addSql('CREATE TABLE vocabulary.botany_elements (id SMALLINT NOT NULL, value VARCHAR(255) NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8E79D1AA1D775834 ON vocabulary.botany_elements (value)');
        $this->addSql('CREATE TABLE botany_seeds (id BIGINT NOT NULL, notes VARCHAR(255) DEFAULT NULL, stratigraphic_unit_id BIGINT NOT NULL, voc_taxonomy_id SMALLINT DEFAULT NULL, voc_element_id SMALLINT DEFAULT NULL, voc_element_part_id SMALLINT DEFAULT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX IDX_BD686190A502ADE ON botany_seeds (stratigraphic_unit_id)');
        $this->addSql('CREATE INDEX IDX_BD6861902A3A1291 ON botany_seeds (voc_taxonomy_id)');
        $this->addSql('CREATE INDEX IDX_BD686190A28A5B17 ON botany_seeds (voc_element_id)');
        $this->addSql('CREATE INDEX IDX_BD68619024B9A4F9 ON botany_seeds (voc_element_part_id)');
        $this->addSql('CREATE TABLE vocabulary.botany_taxonomy (id SMALLINT NOT NULL, value VARCHAR(255) NOT NULL, vernacular_name VARCHAR(255) NOT NULL, class VARCHAR(255) NOT NULL, family VARCHAR(255) DEFAULT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_37216ECA1D775834 ON vocabulary.botany_taxonomy (value)');
        $this->addSql('ALTER TABLE botany_seeds ADD CONSTRAINT FK_BD686190A502ADE FOREIGN KEY (stratigraphic_unit_id) REFERENCES sus (id) ON DELETE RESTRICT NOT DEFERRABLE');
        $this->addSql('ALTER TABLE botany_seeds ADD CONSTRAINT FK_BD6861902A3A1291 FOREIGN KEY (voc_taxonomy_id) REFERENCES vocabulary.botany_taxonomy (id) ON DELETE RESTRICT NOT DEFERRABLE');
        $this->addSql('ALTER TABLE botany_seeds ADD CONSTRAINT FK_BD686190A28A5B17 FOREIGN KEY (voc_element_id) REFERENCES vocabulary.botany_elements (id) ON DELETE RESTRICT NOT DEFERRABLE');
        $this->addSql('ALTER TABLE botany_seeds ADD CONSTRAINT FK_BD68619024B9A4F9 FOREIGN KEY (voc_element_part_id) REFERENCES vocabulary.botany_element_parts (id) ON DELETE RESTRICT NOT DEFERRABLE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP SEQUENCE vocabulary.botany_element_parts_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE vocabulary.botany_elements_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE botany_seed_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE vocabulary.botany_taxonomy_id_seq CASCADE');
        $this->addSql('ALTER TABLE botany_seeds DROP CONSTRAINT FK_BD686190A502ADE');
        $this->addSql('ALTER TABLE botany_seeds DROP CONSTRAINT FK_BD6861902A3A1291');
        $this->addSql('ALTER TABLE botany_seeds DROP CONSTRAINT FK_BD686190A28A5B17');
        $this->addSql('ALTER TABLE botany_seeds DROP CONSTRAINT FK_BD68619024B9A4F9');
        $this->addSql('DROP TABLE vocabulary.botany_element_parts');
        $this->addSql('DROP TABLE vocabulary.botany_elements');
        $this->addSql('DROP TABLE botany_seeds');
        $this->addSql('DROP TABLE vocabulary.botany_taxonomy');
    }
}
