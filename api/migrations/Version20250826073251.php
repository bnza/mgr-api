<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250826073251 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SEQUENCE vocabulary.analysis_types_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE pottery_analyses_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE vocabulary.analysis_types (id SMALLINT NOT NULL, type_group VARCHAR(255) NOT NULL, value VARCHAR(255) NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_4614CD7663CCC3321D775834 ON vocabulary.analysis_types (type_group, value)');
        $this->addSql('CREATE TABLE pottery_analyses (id BIGINT NOT NULL, summary TEXT DEFAULT NULL, pottery_id BIGINT NOT NULL, analysis_type_id SMALLINT NOT NULL, document_id BIGINT DEFAULT NULL, raw_data_id BIGINT DEFAULT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX IDX_D449D3F4F23816BB ON pottery_analyses (pottery_id)');
        $this->addSql('CREATE INDEX IDX_D449D3F4BF9DEA95 ON pottery_analyses (analysis_type_id)');
        $this->addSql('CREATE INDEX IDX_D449D3F4C33F7837 ON pottery_analyses (document_id)');
        $this->addSql('CREATE INDEX IDX_D449D3F441B104A4 ON pottery_analyses (raw_data_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_D449D3F4F23816BBBF9DEA95 ON pottery_analyses (pottery_id, analysis_type_id)');
        $this->addSql('ALTER TABLE pottery_analyses ADD CONSTRAINT FK_D449D3F4F23816BB FOREIGN KEY (pottery_id) REFERENCES potteries (id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('ALTER TABLE pottery_analyses ADD CONSTRAINT FK_D449D3F4BF9DEA95 FOREIGN KEY (analysis_type_id) REFERENCES vocabulary.analysis_types (id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('ALTER TABLE pottery_analyses ADD CONSTRAINT FK_D449D3F4C33F7837 FOREIGN KEY (document_id) REFERENCES media_objects (id) NOT DEFERRABLE');
        $this->addSql('ALTER TABLE pottery_analyses ADD CONSTRAINT FK_D449D3F441B104A4 FOREIGN KEY (raw_data_id) REFERENCES media_objects (id) NOT DEFERRABLE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP SEQUENCE vocabulary.analysis_types_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE pottery_analyses_id_seq CASCADE');
        $this->addSql('ALTER TABLE pottery_analyses DROP CONSTRAINT FK_D449D3F4F23816BB');
        $this->addSql('ALTER TABLE pottery_analyses DROP CONSTRAINT FK_D449D3F4BF9DEA95');
        $this->addSql('ALTER TABLE pottery_analyses DROP CONSTRAINT FK_D449D3F4C33F7837');
        $this->addSql('ALTER TABLE pottery_analyses DROP CONSTRAINT FK_D449D3F441B104A4');
        $this->addSql('DROP TABLE vocabulary.analysis_types');
        $this->addSql('DROP TABLE pottery_analyses');
    }
}
