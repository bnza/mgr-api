<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251006074834 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP SEQUENCE botany_seed_id_seq CASCADE');
        $this->addSql('CREATE SEQUENCE botany_item_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE botany_charcoals (id BIGINT NOT NULL, notes VARCHAR(255) DEFAULT NULL, stratigraphic_unit_id BIGINT NOT NULL, voc_taxonomy_id SMALLINT DEFAULT NULL, voc_element_id SMALLINT DEFAULT NULL, voc_element_part_id SMALLINT DEFAULT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX IDX_9114F6E9A502ADE ON botany_charcoals (stratigraphic_unit_id)');
        $this->addSql('CREATE INDEX IDX_9114F6E92A3A1291 ON botany_charcoals (voc_taxonomy_id)');
        $this->addSql('CREATE INDEX IDX_9114F6E9A28A5B17 ON botany_charcoals (voc_element_id)');
        $this->addSql('CREATE INDEX IDX_9114F6E924B9A4F9 ON botany_charcoals (voc_element_part_id)');
        $this->addSql('ALTER TABLE botany_charcoals ADD CONSTRAINT FK_9114F6E9A502ADE FOREIGN KEY (stratigraphic_unit_id) REFERENCES sus (id) ON DELETE RESTRICT NOT DEFERRABLE');
        $this->addSql('ALTER TABLE botany_charcoals ADD CONSTRAINT FK_9114F6E92A3A1291 FOREIGN KEY (voc_taxonomy_id) REFERENCES vocabulary.botany_taxonomy (id) ON DELETE RESTRICT NOT DEFERRABLE');
        $this->addSql('ALTER TABLE botany_charcoals ADD CONSTRAINT FK_9114F6E9A28A5B17 FOREIGN KEY (voc_element_id) REFERENCES vocabulary.botany_elements (id) ON DELETE RESTRICT NOT DEFERRABLE');
        $this->addSql('ALTER TABLE botany_charcoals ADD CONSTRAINT FK_9114F6E924B9A4F9 FOREIGN KEY (voc_element_part_id) REFERENCES vocabulary.botany_element_parts (id) ON DELETE RESTRICT NOT DEFERRABLE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP SEQUENCE botany_item_id_seq CASCADE');
        $this->addSql('CREATE SEQUENCE botany_seed_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('ALTER TABLE botany_charcoals DROP CONSTRAINT FK_9114F6E9A502ADE');
        $this->addSql('ALTER TABLE botany_charcoals DROP CONSTRAINT FK_9114F6E92A3A1291');
        $this->addSql('ALTER TABLE botany_charcoals DROP CONSTRAINT FK_9114F6E9A28A5B17');
        $this->addSql('ALTER TABLE botany_charcoals DROP CONSTRAINT FK_9114F6E924B9A4F9');
        $this->addSql('DROP TABLE botany_charcoals');
    }
}
