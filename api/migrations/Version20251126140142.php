<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251126140142 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE media_object_potteries (description TEXT DEFAULT NULL, id BIGINT NOT NULL, media_object_id BIGINT NOT NULL, item_id BIGINT NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX IDX_9887E6D064DE5A5 ON media_object_potteries (media_object_id)');
        $this->addSql('CREATE INDEX IDX_9887E6D0126F525E ON media_object_potteries (item_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_9887E6D0126F525E64DE5A5 ON media_object_potteries (item_id, media_object_id)');
        $this->addSql('ALTER TABLE media_object_potteries ADD CONSTRAINT FK_9887E6D064DE5A5 FOREIGN KEY (media_object_id) REFERENCES media_objects (id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('ALTER TABLE media_object_potteries ADD CONSTRAINT FK_9887E6D0126F525E FOREIGN KEY (item_id) REFERENCES potteries (id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('ALTER TABLE vocabulary.history_locations ALTER point TYPE Geography(Point,4326)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE media_object_potteries DROP CONSTRAINT FK_9887E6D064DE5A5');
        $this->addSql('ALTER TABLE media_object_potteries DROP CONSTRAINT FK_9887E6D0126F525E');
        $this->addSql('DROP TABLE media_object_potteries');
        $this->addSql('ALTER TABLE vocabulary.history_locations ALTER point TYPE Geography');
    }
}
