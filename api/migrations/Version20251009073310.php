<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251009073310 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE analysis_individuals (summary TEXT DEFAULT NULL, id BIGINT NOT NULL, analysis_id BIGINT NOT NULL, subject_id BIGINT NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX IDX_D9985D027941003F ON analysis_individuals (analysis_id)');
        $this->addSql('CREATE INDEX IDX_D9985D0223EDC87 ON analysis_individuals (subject_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_D9985D0223EDC877941003F ON analysis_individuals (subject_id, analysis_id)');
        $this->addSql('ALTER TABLE analysis_individuals ADD CONSTRAINT FK_D9985D027941003F FOREIGN KEY (analysis_id) REFERENCES analyses (id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('ALTER TABLE analysis_individuals ADD CONSTRAINT FK_D9985D0223EDC87 FOREIGN KEY (subject_id) REFERENCES individuals (id) ON DELETE CASCADE NOT DEFERRABLE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE analysis_individuals DROP CONSTRAINT FK_D9985D027941003F');
        $this->addSql('ALTER TABLE analysis_individuals DROP CONSTRAINT FK_D9985D0223EDC87');
        $this->addSql('DROP TABLE analysis_individuals');
    }
}
