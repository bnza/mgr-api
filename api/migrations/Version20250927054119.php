<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250927054119 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE analyses_microstratigraphic_units DROP CONSTRAINT fk_1a1a0b5223edc87');
        $this->addSql('ALTER TABLE analyses_microstratigraphic_units ADD CONSTRAINT FK_1A1A0B5223EDC87 FOREIGN KEY (subject_id) REFERENCES samples (id) ON DELETE CASCADE NOT DEFERRABLE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE analyses_microstratigraphic_units DROP CONSTRAINT FK_1A1A0B5223EDC87');
        $this->addSql('ALTER TABLE analyses_microstratigraphic_units ADD CONSTRAINT fk_1a1a0b5223edc87 FOREIGN KEY (subject_id) REFERENCES mus (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }
}
