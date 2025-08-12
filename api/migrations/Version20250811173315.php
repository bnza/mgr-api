<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250811173315 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SEQUENCE media_object_join_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE media_object_stratigraphic_units (description TEXT DEFAULT NULL, id BIGINT NOT NULL, media_object_id BIGINT NOT NULL, item_id BIGINT NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX IDX_2DAB12CC64DE5A5 ON media_object_stratigraphic_units (media_object_id)');
        $this->addSql('CREATE INDEX IDX_2DAB12CC126F525E ON media_object_stratigraphic_units (item_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_2DAB12CC126F525E64DE5A5 ON media_object_stratigraphic_units (item_id, media_object_id)');
        $this->addSql('ALTER TABLE media_object_stratigraphic_units ADD CONSTRAINT FK_2DAB12CC64DE5A5 FOREIGN KEY (media_object_id) REFERENCES media_objects (id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('ALTER TABLE media_object_stratigraphic_units ADD CONSTRAINT FK_2DAB12CC126F525E FOREIGN KEY (item_id) REFERENCES sus (id) ON DELETE CASCADE NOT DEFERRABLE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP SEQUENCE media_object_join_id_seq CASCADE');
        $this->addSql('ALTER TABLE media_object_stratigraphic_units DROP CONSTRAINT FK_2DAB12CC64DE5A5');
        $this->addSql('ALTER TABLE media_object_stratigraphic_units DROP CONSTRAINT FK_2DAB12CC126F525E');
        $this->addSql('DROP TABLE media_object_stratigraphic_units');
    }
}
