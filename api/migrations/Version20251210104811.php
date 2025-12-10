<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251210104811 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX uniq_ac86883c772e836a');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_AC86883CBF9DEA95BB827337772E836A ON analyses (analysis_type_id, year, identifier)');
        $this->addSql('ALTER TABLE vocabulary.history_locations ALTER point TYPE Geography(Point,4326)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX UNIQ_AC86883CBF9DEA95BB827337772E836A');
        $this->addSql('CREATE UNIQUE INDEX uniq_ac86883c772e836a ON analyses (identifier)');
        $this->addSql('ALTER TABLE vocabulary.history_locations ALTER point TYPE Geography');
    }
}
