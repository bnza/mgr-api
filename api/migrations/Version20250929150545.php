<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250929150545 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE analyses_anthropology (summary TEXT DEFAULT NULL, id BIGINT NOT NULL, analysis_id BIGINT NOT NULL, subject_id BIGINT NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX IDX_ED2FD06A7941003F ON analyses_anthropology (analysis_id)');
        $this->addSql('CREATE INDEX IDX_ED2FD06A23EDC87 ON analyses_anthropology (subject_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_ED2FD06A23EDC877941003F ON analyses_anthropology (subject_id, analysis_id)');
        $this->addSql('ALTER TABLE analyses_anthropology ADD CONSTRAINT FK_ED2FD06A7941003F FOREIGN KEY (analysis_id) REFERENCES analyses (id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('ALTER TABLE analyses_anthropology ADD CONSTRAINT FK_ED2FD06A23EDC87 FOREIGN KEY (subject_id) REFERENCES sites (id) ON DELETE CASCADE NOT DEFERRABLE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE analyses_anthropology DROP CONSTRAINT FK_ED2FD06A7941003F');
        $this->addSql('ALTER TABLE analyses_anthropology DROP CONSTRAINT FK_ED2FD06A23EDC87');
        $this->addSql('DROP TABLE analyses_anthropology');
    }
}
