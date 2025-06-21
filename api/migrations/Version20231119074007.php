<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20231119074007 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create PostGIS extension';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE EXTENSION IF NOT EXISTS plpgsql;');
        $this->addSql('DROP EXTENSION IF EXISTS postgis_topology;');
        $this->addSql('DROP EXTENSION IF EXISTS postgis_tiger_geocoder;');
        $this->addSql('DROP SCHEMA IF EXISTS topology;');
        $this->addSql('DROP SCHEMA IF EXISTS tiger;');
        $this->addSql('DROP SCHEMA IF EXISTS tiger_data;');
        $this->addSql('CREATE EXTENSION IF NOT EXISTS postgis;');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP EXTENSION postgis;');
    }
}
