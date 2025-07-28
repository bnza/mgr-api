<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250726152633 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SEQUENCE site_cultural_contexts_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE vocabulary.cultural_contexts (id SMALLINT NOT NULL, value VARCHAR(255) NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_DC0C4A0E1D775834 ON vocabulary.cultural_contexts (value)');
        $this->addSql('CREATE TABLE site_cultural_contexts (id BIGINT NOT NULL, site_id BIGINT NOT NULL, cultural_context_id SMALLINT NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX IDX_35F57A8EF6BD1646 ON site_cultural_contexts (site_id)');
        $this->addSql('CREATE INDEX IDX_35F57A8E71C15152 ON site_cultural_contexts (cultural_context_id)');
        $this->addSql('ALTER TABLE site_cultural_contexts ADD CONSTRAINT FK_35F57A8EF6BD1646 FOREIGN KEY (site_id) REFERENCES sites (id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('ALTER TABLE site_cultural_contexts ADD CONSTRAINT FK_35F57A8E71C15152 FOREIGN KEY (cultural_context_id) REFERENCES vocabulary.cultural_contexts (id) ON DELETE CASCADE NOT DEFERRABLE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP SEQUENCE site_cultural_contexts_id_seq CASCADE');
        $this->addSql('ALTER TABLE site_cultural_contexts DROP CONSTRAINT FK_35F57A8EF6BD1646');
        $this->addSql('ALTER TABLE site_cultural_contexts DROP CONSTRAINT FK_35F57A8E71C15152');
        $this->addSql('DROP TABLE vocabulary.cultural_contexts');
        $this->addSql('DROP TABLE site_cultural_contexts');
    }
}
