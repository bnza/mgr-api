<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251118101106 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SEQUENCE vocabulary.history_animals_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE history_animals (id BIGINT NOT NULL, chronology_lower SMALLINT NOT NULL, chronology_upper SMALLINT NOT NULL, reference VARCHAR(255) NOT NULL, notes VARCHAR(255) DEFAULT NULL, animal_id SMALLINT NOT NULL, location_id BIGINT NOT NULL, created_by_id UUID DEFAULT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX IDX_B20B45378E962C16 ON history_animals (animal_id)');
        $this->addSql('CREATE INDEX IDX_B20B453764D218E ON history_animals (location_id)');
        $this->addSql('CREATE INDEX IDX_B20B4537B03A8386 ON history_animals (created_by_id)');
        $this->addSql('CREATE TABLE vocabulary.history_animals (id SMALLINT NOT NULL, value VARCHAR(255) NOT NULL, taxonomy_id SMALLINT DEFAULT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX IDX_96972B5D9557E6F6 ON vocabulary.history_animals (taxonomy_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_96972B5D1D775834 ON vocabulary.history_animals (value)');
        $this->addSql('ALTER TABLE history_animals ADD CONSTRAINT FK_B20B45378E962C16 FOREIGN KEY (animal_id) REFERENCES vocabulary.history_animals (id) ON DELETE RESTRICT NOT DEFERRABLE');
        $this->addSql('ALTER TABLE history_animals ADD CONSTRAINT FK_B20B453764D218E FOREIGN KEY (location_id) REFERENCES history_locations (id) ON DELETE RESTRICT NOT DEFERRABLE');
        $this->addSql('ALTER TABLE history_animals ADD CONSTRAINT FK_B20B4537B03A8386 FOREIGN KEY (created_by_id) REFERENCES auth.users (id) ON DELETE RESTRICT NOT DEFERRABLE');
        $this->addSql('ALTER TABLE vocabulary.history_animals ADD CONSTRAINT FK_96972B5D9557E6F6 FOREIGN KEY (taxonomy_id) REFERENCES vocabulary.zoo_taxonomy (id) ON DELETE RESTRICT NOT DEFERRABLE');
        $this->addSql('ALTER TABLE history_locations ALTER point TYPE Geography(Point,4326)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP SEQUENCE vocabulary.history_animals_id_seq CASCADE');
        $this->addSql('ALTER TABLE history_animals DROP CONSTRAINT FK_B20B45378E962C16');
        $this->addSql('ALTER TABLE history_animals DROP CONSTRAINT FK_B20B453764D218E');
        $this->addSql('ALTER TABLE history_animals DROP CONSTRAINT FK_B20B4537B03A8386');
        $this->addSql('ALTER TABLE vocabulary.history_animals DROP CONSTRAINT FK_96972B5D9557E6F6');
        $this->addSql('DROP TABLE history_animals');
        $this->addSql('DROP TABLE vocabulary.history_animals');
        $this->addSql('ALTER TABLE history_locations ALTER point TYPE Geography');
    }
}
