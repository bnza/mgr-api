<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250818144740 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SEQUENCE vocabulary.decoration_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE pottery_decoration_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE vocabulary.decoration (id SMALLINT NOT NULL, value VARCHAR(255) NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_4B9C50A41D775834 ON vocabulary.decoration (value)');
        $this->addSql('CREATE TABLE pottery_decoration (id BIGINT NOT NULL, pottery_id BIGINT NOT NULL, decoration_id SMALLINT NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX IDX_F8FE3052F23816BB ON pottery_decoration (pottery_id)');
        $this->addSql('CREATE INDEX IDX_F8FE30523446DFC4 ON pottery_decoration (decoration_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_F8FE3052F23816BB3446DFC4 ON pottery_decoration (pottery_id, decoration_id)');
        $this->addSql('ALTER TABLE pottery_decoration ADD CONSTRAINT FK_F8FE3052F23816BB FOREIGN KEY (pottery_id) REFERENCES potteries (id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('ALTER TABLE pottery_decoration ADD CONSTRAINT FK_F8FE30523446DFC4 FOREIGN KEY (decoration_id) REFERENCES vocabulary.decoration (id) ON DELETE CASCADE NOT DEFERRABLE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP SEQUENCE vocabulary.decoration_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE pottery_decoration_id_seq CASCADE');
        $this->addSql('ALTER TABLE pottery_decoration DROP CONSTRAINT FK_F8FE3052F23816BB');
        $this->addSql('ALTER TABLE pottery_decoration DROP CONSTRAINT FK_F8FE30523446DFC4');
        $this->addSql('DROP TABLE vocabulary.decoration');
        $this->addSql('DROP TABLE pottery_decoration');
    }
}
