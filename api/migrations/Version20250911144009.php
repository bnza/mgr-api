<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250911144009 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SEQUENCE context_zoo_analyses_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE context_zoo_analysis_taxonomies_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE context_zoo_analyses (id BIGINT NOT NULL, summary TEXT DEFAULT NULL, item_id BIGINT NOT NULL, analysis_type_id SMALLINT NOT NULL, document_id BIGINT DEFAULT NULL, raw_data_id BIGINT DEFAULT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX IDX_50370C09126F525E ON context_zoo_analyses (item_id)');
        $this->addSql('CREATE INDEX IDX_50370C09BF9DEA95 ON context_zoo_analyses (analysis_type_id)');
        $this->addSql('CREATE INDEX IDX_50370C09C33F7837 ON context_zoo_analyses (document_id)');
        $this->addSql('CREATE INDEX IDX_50370C0941B104A4 ON context_zoo_analyses (raw_data_id)');
        $this->addSql('CREATE TABLE context_zoo_analysis_taxonomies (id BIGINT NOT NULL, analysis_id BIGINT NOT NULL, taxonomy_id SMALLINT NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX IDX_A4DD49AC7941003F ON context_zoo_analysis_taxonomies (analysis_id)');
        $this->addSql('CREATE INDEX IDX_A4DD49AC9557E6F6 ON context_zoo_analysis_taxonomies (taxonomy_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_A4DD49AC7941003F9557E6F6 ON context_zoo_analysis_taxonomies (analysis_id, taxonomy_id)');
        $this->addSql('ALTER TABLE context_zoo_analyses ADD CONSTRAINT FK_50370C09126F525E FOREIGN KEY (item_id) REFERENCES contexts (id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('ALTER TABLE context_zoo_analyses ADD CONSTRAINT FK_50370C09BF9DEA95 FOREIGN KEY (analysis_type_id) REFERENCES vocabulary.analysis_types (id) ON DELETE RESTRICT NOT DEFERRABLE');
        $this->addSql('ALTER TABLE context_zoo_analyses ADD CONSTRAINT FK_50370C09C33F7837 FOREIGN KEY (document_id) REFERENCES media_objects (id) NOT DEFERRABLE');
        $this->addSql('ALTER TABLE context_zoo_analyses ADD CONSTRAINT FK_50370C0941B104A4 FOREIGN KEY (raw_data_id) REFERENCES media_objects (id) NOT DEFERRABLE');
        $this->addSql('ALTER TABLE context_zoo_analysis_taxonomies ADD CONSTRAINT FK_A4DD49AC7941003F FOREIGN KEY (analysis_id) REFERENCES context_zoo_analyses (id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('ALTER TABLE context_zoo_analysis_taxonomies ADD CONSTRAINT FK_A4DD49AC9557E6F6 FOREIGN KEY (taxonomy_id) REFERENCES vocabulary.zoo_taxonomy (id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('ALTER TABLE pottery_analyses DROP CONSTRAINT fk_d449d3f4bf9dea95');
        $this->addSql('ALTER TABLE pottery_analyses ADD CONSTRAINT FK_D449D3F4BF9DEA95 FOREIGN KEY (analysis_type_id) REFERENCES vocabulary.analysis_types (id) ON DELETE RESTRICT NOT DEFERRABLE');
        $this->addSql('ALTER TABLE site_cultural_contexts DROP CONSTRAINT fk_35f57a8e71c15152');
        $this->addSql('ALTER TABLE site_cultural_contexts ADD CONSTRAINT FK_35F57A8E71C15152 FOREIGN KEY (cultural_context_id) REFERENCES vocabulary.cultural_contexts (id) ON DELETE RESTRICT NOT DEFERRABLE');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_35F57A8EF6BD164671C15152 ON site_cultural_contexts (site_id, cultural_context_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP SEQUENCE context_zoo_analyses_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE context_zoo_analysis_taxonomies_id_seq CASCADE');
        $this->addSql('ALTER TABLE context_zoo_analyses DROP CONSTRAINT FK_50370C09126F525E');
        $this->addSql('ALTER TABLE context_zoo_analyses DROP CONSTRAINT FK_50370C09BF9DEA95');
        $this->addSql('ALTER TABLE context_zoo_analyses DROP CONSTRAINT FK_50370C09C33F7837');
        $this->addSql('ALTER TABLE context_zoo_analyses DROP CONSTRAINT FK_50370C0941B104A4');
        $this->addSql('ALTER TABLE context_zoo_analysis_taxonomies DROP CONSTRAINT FK_A4DD49AC7941003F');
        $this->addSql('ALTER TABLE context_zoo_analysis_taxonomies DROP CONSTRAINT FK_A4DD49AC9557E6F6');
        $this->addSql('DROP TABLE context_zoo_analyses');
        $this->addSql('DROP TABLE context_zoo_analysis_taxonomies');
        $this->addSql('ALTER TABLE pottery_analyses DROP CONSTRAINT FK_D449D3F4BF9DEA95');
        $this->addSql('ALTER TABLE pottery_analyses ADD CONSTRAINT fk_d449d3f4bf9dea95 FOREIGN KEY (analysis_type_id) REFERENCES vocabulary.analysis_types (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE site_cultural_contexts DROP CONSTRAINT FK_35F57A8E71C15152');
        $this->addSql('DROP INDEX UNIQ_35F57A8EF6BD164671C15152');
        $this->addSql('ALTER TABLE site_cultural_contexts ADD CONSTRAINT fk_35f57a8e71c15152 FOREIGN KEY (cultural_context_id) REFERENCES vocabulary.cultural_contexts (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }
}
