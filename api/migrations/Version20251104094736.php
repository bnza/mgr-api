<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251104094736 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE analysis_botany_charcoals (summary TEXT DEFAULT NULL, id BIGINT NOT NULL, analysis_id BIGINT NOT NULL, subject_id BIGINT NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX IDX_538700097941003F ON analysis_botany_charcoals (analysis_id)');
        $this->addSql('CREATE INDEX IDX_5387000923EDC87 ON analysis_botany_charcoals (subject_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_5387000923EDC877941003F ON analysis_botany_charcoals (subject_id, analysis_id)');
        $this->addSql('CREATE TABLE analysis_botany_seeds (summary TEXT DEFAULT NULL, id BIGINT NOT NULL, analysis_id BIGINT NOT NULL, subject_id BIGINT NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX IDX_75FEF2947941003F ON analysis_botany_seeds (analysis_id)');
        $this->addSql('CREATE INDEX IDX_75FEF29423EDC87 ON analysis_botany_seeds (subject_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_75FEF29423EDC877941003F ON analysis_botany_seeds (subject_id, analysis_id)');
        $this->addSql('ALTER TABLE analysis_botany_charcoals ADD CONSTRAINT FK_538700097941003F FOREIGN KEY (analysis_id) REFERENCES analyses (id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('ALTER TABLE analysis_botany_charcoals ADD CONSTRAINT FK_5387000923EDC87 FOREIGN KEY (subject_id) REFERENCES botany_charcoals (id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('ALTER TABLE analysis_botany_seeds ADD CONSTRAINT FK_75FEF2947941003F FOREIGN KEY (analysis_id) REFERENCES analyses (id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('ALTER TABLE analysis_botany_seeds ADD CONSTRAINT FK_75FEF29423EDC87 FOREIGN KEY (subject_id) REFERENCES botany_seeds (id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('ALTER TABLE history_locations ALTER point TYPE Geography(Point,4326)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE analysis_botany_charcoals DROP CONSTRAINT FK_538700097941003F');
        $this->addSql('ALTER TABLE analysis_botany_charcoals DROP CONSTRAINT FK_5387000923EDC87');
        $this->addSql('ALTER TABLE analysis_botany_seeds DROP CONSTRAINT FK_75FEF2947941003F');
        $this->addSql('ALTER TABLE analysis_botany_seeds DROP CONSTRAINT FK_75FEF29423EDC87');
        $this->addSql('DROP TABLE analysis_botany_charcoals');
        $this->addSql('DROP TABLE analysis_botany_seeds');
        $this->addSql('ALTER TABLE history_locations ALTER point TYPE Geography');
    }
}
