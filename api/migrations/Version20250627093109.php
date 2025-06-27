<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250627093109 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            <<<'SQL'
            CREATE OR REPLACE FUNCTION validate_context_stratigraphic_units_site()
            RETURNS TRIGGER AS $$
            BEGIN
                IF (SELECT site_id FROM sus WHERE id = NEW.su_id) !=
                   (SELECT site_id FROM contexts WHERE id = NEW.context_id) THEN
                    RAISE EXCEPTION 'Stratigraphic unit and context must belong to the same site';
                END IF;
                RETURN NEW;
            END;
            $$ LANGUAGE plpgsql;

        SQL
        );

        $this->addSql(
            <<<'SQL'
            CREATE TRIGGER trg_enforce_context_stratigraphic_unit_site_consistency
            BEFORE INSERT OR UPDATE ON context_stratigraphic_units
            FOR EACH ROW EXECUTE FUNCTION validate_context_stratigraphic_units_site();
        SQL
        );
    }

    public function down(Schema $schema): void
    {

        $this->addSql(
            <<<'SQL'
        DROP TRIGGER IF EXISTS trg_enforce_context_stratigraphic_unit_site_consistency ON context_stratigraphic_units;
        SQL
        );

        $this->addSql(
            <<<'SQL'
        DROP FUNCTION IF EXISTS validate_context_stratigraphic_units_site;
        SQL
        );
    }
}
