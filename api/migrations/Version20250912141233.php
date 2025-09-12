<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250912141233 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SEQUENCE zoo_tooth_analyses_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE zoo_tooth_analyses (id BIGINT NOT NULL, summary TEXT DEFAULT NULL, item_id BIGINT NOT NULL, analysis_type_id SMALLINT NOT NULL, document_id BIGINT DEFAULT NULL, raw_data_id BIGINT DEFAULT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX IDX_C294B08126F525E ON zoo_tooth_analyses (item_id)');
        $this->addSql('CREATE INDEX IDX_C294B08BF9DEA95 ON zoo_tooth_analyses (analysis_type_id)');
        $this->addSql('CREATE INDEX IDX_C294B08C33F7837 ON zoo_tooth_analyses (document_id)');
        $this->addSql('CREATE INDEX IDX_C294B0841B104A4 ON zoo_tooth_analyses (raw_data_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_C294B08126F525EBF9DEA95 ON zoo_tooth_analyses (item_id, analysis_type_id)');
        $this->addSql('ALTER TABLE zoo_tooth_analyses ADD CONSTRAINT FK_C294B08126F525E FOREIGN KEY (item_id) REFERENCES zoo_teeth (id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('ALTER TABLE zoo_tooth_analyses ADD CONSTRAINT FK_C294B08BF9DEA95 FOREIGN KEY (analysis_type_id) REFERENCES vocabulary.analysis_types (id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('ALTER TABLE zoo_tooth_analyses ADD CONSTRAINT FK_C294B08C33F7837 FOREIGN KEY (document_id) REFERENCES media_objects (id) NOT DEFERRABLE');
        $this->addSql('ALTER TABLE zoo_tooth_analyses ADD CONSTRAINT FK_C294B0841B104A4 FOREIGN KEY (raw_data_id) REFERENCES media_objects (id) NOT DEFERRABLE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP SEQUENCE zoo_tooth_analyses_id_seq CASCADE');
        $this->addSql('ALTER TABLE zoo_tooth_analyses DROP CONSTRAINT FK_C294B08126F525E');
        $this->addSql('ALTER TABLE zoo_tooth_analyses DROP CONSTRAINT FK_C294B08BF9DEA95');
        $this->addSql('ALTER TABLE zoo_tooth_analyses DROP CONSTRAINT FK_C294B08C33F7837');
        $this->addSql('ALTER TABLE zoo_tooth_analyses DROP CONSTRAINT FK_C294B0841B104A4');
        $this->addSql('DROP TABLE zoo_tooth_analyses');
    }
}
