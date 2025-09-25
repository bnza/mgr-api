<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250925150355 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SEQUENCE mus_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE mus (id BIGINT NOT NULL, identifier VARCHAR(255) NOT NULL, notes TEXT DEFAULT NULL, stratigraphic_unit_id BIGINT NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX IDX_240A2C54A502ADE ON mus (stratigraphic_unit_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_240A2C54A502ADE772E836A ON mus (stratigraphic_unit_id, identifier)');
        $this->addSql('ALTER TABLE mus ADD CONSTRAINT FK_240A2C54A502ADE FOREIGN KEY (stratigraphic_unit_id) REFERENCES sus (id) ON DELETE CASCADE NOT DEFERRABLE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP SEQUENCE mus_id_seq CASCADE');
        $this->addSql('ALTER TABLE mus DROP CONSTRAINT FK_240A2C54A502ADE');
        $this->addSql('DROP TABLE mus');
    }
}
