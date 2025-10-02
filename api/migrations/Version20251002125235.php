<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251002125235 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE sediment_core_depths ALTER depth_min TYPE NUMERIC(5, 1)');
        $this->addSql('ALTER TABLE sediment_core_depths ALTER depth_max TYPE NUMERIC(5, 1)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE sediment_core_depths ALTER depth_min TYPE NUMERIC(1, 4)');
        $this->addSql('ALTER TABLE sediment_core_depths ALTER depth_max TYPE NUMERIC(1, 4)');
    }
}
