<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251030180921 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP SEQUENCE vocabulary.context_types_id_seq CASCADE');
        $this->addSql('CREATE SEQUENCE history_locations_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE history_cit_item_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE vocabulary.history_plants_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE history_locations (id BIGINT NOT NULL, name VARCHAR(255) NOT NULL, point Geography(Point,4326) NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_81AF19585E237E06 ON history_locations (name)');
        $this->addSql('CREATE TABLE history_plants (id BIGINT NOT NULL, chronology_lower SMALLINT NOT NULL, chronology_upper SMALLINT NOT NULL, reference VARCHAR(255) NOT NULL, notes VARCHAR(255) DEFAULT NULL, plant_id SMALLINT NOT NULL, location_id BIGINT NOT NULL, created_by_id UUID DEFAULT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX IDX_762190E51D935652 ON history_plants (plant_id)');
        $this->addSql('CREATE INDEX IDX_762190E564D218E ON history_plants (location_id)');
        $this->addSql('CREATE INDEX IDX_762190E5B03A8386 ON history_plants (created_by_id)');
        $this->addSql('CREATE TABLE vocabulary.history_plants (id SMALLINT NOT NULL, value VARCHAR(255) NOT NULL, taxonomy_id SMALLINT DEFAULT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX IDX_5EEC5C169557E6F6 ON vocabulary.history_plants (taxonomy_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_5EEC5C161D775834 ON vocabulary.history_plants (value)');
        $this->addSql('ALTER TABLE history_plants ADD CONSTRAINT FK_762190E51D935652 FOREIGN KEY (plant_id) REFERENCES vocabulary.history_plants (id) ON DELETE RESTRICT NOT DEFERRABLE');
        $this->addSql('ALTER TABLE history_plants ADD CONSTRAINT FK_762190E564D218E FOREIGN KEY (location_id) REFERENCES history_locations (id) ON DELETE RESTRICT NOT DEFERRABLE');
        $this->addSql('ALTER TABLE history_plants ADD CONSTRAINT FK_762190E5B03A8386 FOREIGN KEY (created_by_id) REFERENCES auth.users (id) ON DELETE RESTRICT NOT DEFERRABLE');
        $this->addSql('ALTER TABLE vocabulary.history_plants ADD CONSTRAINT FK_5EEC5C169557E6F6 FOREIGN KEY (taxonomy_id) REFERENCES vocabulary.botany_taxonomy (id) ON DELETE RESTRICT NOT DEFERRABLE');
        $this->addSql('DROP TABLE vocabulary.context_types');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP SEQUENCE history_locations_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE history_cit_item_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE vocabulary.history_plants_id_seq CASCADE');
        $this->addSql('CREATE SEQUENCE vocabulary.context_types_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE vocabulary.context_types (id SMALLINT NOT NULL, type_group VARCHAR(255) NOT NULL, value VARCHAR(255) NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE UNIQUE INDEX uniq_c753887663ccc3321d775834 ON vocabulary.context_types (type_group, value)');
        $this->addSql('ALTER TABLE history_plants DROP CONSTRAINT FK_762190E51D935652');
        $this->addSql('ALTER TABLE history_plants DROP CONSTRAINT FK_762190E564D218E');
        $this->addSql('ALTER TABLE history_plants DROP CONSTRAINT FK_762190E5B03A8386');
        $this->addSql('ALTER TABLE vocabulary.history_plants DROP CONSTRAINT FK_5EEC5C169557E6F6');
        $this->addSql('DROP TABLE history_locations');
        $this->addSql('DROP TABLE history_plants');
        $this->addSql('DROP TABLE vocabulary.history_plants');
    }
}
