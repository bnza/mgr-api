<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250725144549 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX uniq_32b2a22ef6bd164696901f54');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_32B2A22EF6BD1646BB82733796901F54 ON sus (site_id, year, number)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX UNIQ_32B2A22EF6BD1646BB82733796901F54');
        $this->addSql('CREATE UNIQUE INDEX uniq_32b2a22ef6bd164696901f54 ON sus (site_id, number)');
    }
}
