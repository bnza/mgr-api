<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250910095858 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SEQUENCE zoo_bone_analyses_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE zoo_bone_analyses (id BIGINT NOT NULL, summary TEXT DEFAULT NULL, item_id BIGINT NOT NULL, analysis_type_id SMALLINT NOT NULL, document_id BIGINT DEFAULT NULL, raw_data_id BIGINT DEFAULT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX IDX_2ABFC4AB126F525E ON zoo_bone_analyses (item_id)');
        $this->addSql('CREATE INDEX IDX_2ABFC4ABBF9DEA95 ON zoo_bone_analyses (analysis_type_id)');
        $this->addSql('CREATE INDEX IDX_2ABFC4ABC33F7837 ON zoo_bone_analyses (document_id)');
        $this->addSql('CREATE INDEX IDX_2ABFC4AB41B104A4 ON zoo_bone_analyses (raw_data_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_2ABFC4AB126F525EBF9DEA95 ON zoo_bone_analyses (item_id, analysis_type_id)');
        $this->addSql('ALTER TABLE zoo_bone_analyses ADD CONSTRAINT FK_2ABFC4AB126F525E FOREIGN KEY (item_id) REFERENCES zoo_bones (id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('ALTER TABLE zoo_bone_analyses ADD CONSTRAINT FK_2ABFC4ABBF9DEA95 FOREIGN KEY (analysis_type_id) REFERENCES vocabulary.analysis_types (id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('ALTER TABLE zoo_bone_analyses ADD CONSTRAINT FK_2ABFC4ABC33F7837 FOREIGN KEY (document_id) REFERENCES media_objects (id) NOT DEFERRABLE');
        $this->addSql('ALTER TABLE zoo_bone_analyses ADD CONSTRAINT FK_2ABFC4AB41B104A4 FOREIGN KEY (raw_data_id) REFERENCES media_objects (id) NOT DEFERRABLE');
        $this->addSql('ALTER TABLE pottery_analyses DROP CONSTRAINT fk_d449d3f4f23816bb');
        $this->addSql('DROP INDEX uniq_d449d3f4f23816bbbf9dea95');
        $this->addSql('DROP INDEX idx_d449d3f4f23816bb');
        $this->addSql('ALTER TABLE pottery_analyses RENAME COLUMN pottery_id TO item_id');
        $this->addSql('ALTER TABLE pottery_analyses ADD CONSTRAINT FK_D449D3F4126F525E FOREIGN KEY (item_id) REFERENCES potteries (id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('CREATE INDEX IDX_D449D3F4126F525E ON pottery_analyses (item_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_D449D3F4126F525EBF9DEA95 ON pottery_analyses (item_id, analysis_type_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP SEQUENCE zoo_bone_analyses_id_seq CASCADE');
        $this->addSql('ALTER TABLE zoo_bone_analyses DROP CONSTRAINT FK_2ABFC4AB126F525E');
        $this->addSql('ALTER TABLE zoo_bone_analyses DROP CONSTRAINT FK_2ABFC4ABBF9DEA95');
        $this->addSql('ALTER TABLE zoo_bone_analyses DROP CONSTRAINT FK_2ABFC4ABC33F7837');
        $this->addSql('ALTER TABLE zoo_bone_analyses DROP CONSTRAINT FK_2ABFC4AB41B104A4');
        $this->addSql('DROP TABLE zoo_bone_analyses');
        $this->addSql('ALTER TABLE pottery_analyses DROP CONSTRAINT FK_D449D3F4126F525E');
        $this->addSql('DROP INDEX IDX_D449D3F4126F525E');
        $this->addSql('DROP INDEX UNIQ_D449D3F4126F525EBF9DEA95');
        $this->addSql('ALTER TABLE pottery_analyses RENAME COLUMN item_id TO pottery_id');
        $this->addSql('ALTER TABLE pottery_analyses ADD CONSTRAINT fk_d449d3f4f23816bb FOREIGN KEY (pottery_id) REFERENCES potteries (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE UNIQUE INDEX uniq_d449d3f4f23816bbbf9dea95 ON pottery_analyses (pottery_id, analysis_type_id)');
        $this->addSql('CREATE INDEX idx_d449d3f4f23816bb ON pottery_analyses (pottery_id)');
    }
}
