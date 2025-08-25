<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250821074254 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SEQUENCE vocabulary.surface_treatment_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE vocabulary.surface_treatment (id SMALLINT NOT NULL, value VARCHAR(255) NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_2F1E3C0A1D775834 ON vocabulary.surface_treatment (value)');
        $this->addSql('ALTER TABLE potteries ADD inner_color VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE potteries ADD outer_color VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE potteries ADD decoration_motif VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE potteries ADD surface_treatment_id SMALLINT DEFAULT NULL');
        $this->addSql('ALTER TABLE potteries ADD CONSTRAINT FK_91910162EFB04BBB FOREIGN KEY (surface_treatment_id) REFERENCES vocabulary.surface_treatment (id) ON DELETE RESTRICT NOT DEFERRABLE');
        $this->addSql('CREATE INDEX IDX_91910162EFB04BBB ON potteries (surface_treatment_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP SEQUENCE vocabulary.surface_treatment_id_seq CASCADE');
        $this->addSql('DROP TABLE vocabulary.surface_treatment');
        $this->addSql('ALTER TABLE potteries DROP CONSTRAINT FK_91910162EFB04BBB');
        $this->addSql('DROP INDEX IDX_91910162EFB04BBB');
        $this->addSql('ALTER TABLE potteries DROP inner_color');
        $this->addSql('ALTER TABLE potteries DROP outer_color');
        $this->addSql('ALTER TABLE potteries DROP decoration_motif');
        $this->addSql('ALTER TABLE potteries DROP surface_treatment_id');
    }
}
