<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251122093504 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE vocabulary.history_locations ALTER point TYPE Geography(Point,4326)');
        $this->addSql('ALTER TABLE sus ADD chronology_lower SMALLINT DEFAULT NULL');
        $this->addSql('ALTER TABLE sus ADD chronology_upper SMALLINT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE sus DROP chronology_lower');
        $this->addSql('ALTER TABLE sus DROP chronology_upper');
        $this->addSql('ALTER TABLE vocabulary.history_locations ALTER point TYPE Geography');
    }
}
