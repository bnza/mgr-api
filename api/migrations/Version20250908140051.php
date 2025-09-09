<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250908140051 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE media_objects ADD uploaded_by_id UUID DEFAULT NULL');
        $this->addSql('ALTER TABLE media_objects ADD CONSTRAINT FK_D3CD4ABAA2B28FE8 FOREIGN KEY (uploaded_by_id) REFERENCES auth.users (id) ON DELETE SET NULL NOT DEFERRABLE');
        $this->addSql('CREATE INDEX IDX_D3CD4ABAA2B28FE8 ON media_objects (uploaded_by_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE media_objects DROP CONSTRAINT FK_D3CD4ABAA2B28FE8');
        $this->addSql('DROP INDEX IDX_D3CD4ABAA2B28FE8');
        $this->addSql('ALTER TABLE media_objects DROP uploaded_by_id');
    }
}
