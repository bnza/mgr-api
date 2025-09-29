<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250627142200 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Set tables checks, triggers, functions';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            <<<'SQL'
            ALTER TABLE sites ADD CONSTRAINT chk_chronology CHECK (chronology_upper IS NULL OR chronology_lower IS NULL OR chronology_upper >= chronology_lower);
       SQL
        );

        $this->addSql(
            <<<'SQL'
            ALTER TABLE individuals ADD CONSTRAINT chk_sex CHECK (sex IS NULL OR sex IN ('F', 'M', '?'));
            SQL
        );

        $this->addSql(
            <<<'SQL'
            COMMENT ON CONSTRAINT chk_sex ON individuals IS 'Sex must be F (female), M (male), or ? (indeterminate)';
            SQL
        );

        $this->addSql(
            <<<'SQL'
            ALTER TABLE zoo_bones ADD CONSTRAINT chk_sex CHECK (side IS NULL OR side IN ('L', 'R', '?'));
            SQL
        );

        $this->addSql(
            <<<'SQL'
            COMMENT ON CONSTRAINT chk_sex ON zoo_bones IS 'Sex must be L (left), R (right), or ? (indeterminate)';
            SQL
        );

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

        // Enforce: sample_stratigraphic_units.sample_id site == sus.site_id
        $this->addSql(
            <<<'SQL'
            CREATE OR REPLACE FUNCTION validate_sample_stratigraphic_units_site()
            RETURNS TRIGGER AS $$
            BEGIN
                IF (SELECT site_id FROM samples WHERE id = NEW.sample_id) !=
                   (SELECT site_id FROM sus     WHERE id = NEW.su_id) THEN
                    RAISE EXCEPTION 'Sample and stratigraphic unit must belong to the same site';
                END IF;
                RETURN NEW;
            END;
            $$ LANGUAGE plpgsql;
        SQL
        );

        $this->addSql(
            <<<'SQL'
            CREATE TRIGGER trg_enforce_sample_stratigraphic_unit_site_consistency
            BEFORE INSERT OR UPDATE ON sample_stratigraphic_units
            FOR EACH ROW EXECUTE FUNCTION validate_sample_stratigraphic_units_site();
        SQL
        );

        $this->addSql(
            <<<'SQL'
            CREATE OR REPLACE FUNCTION unaccent_immutable(text)
            RETURNS text
            AS $$
              SELECT public.unaccent('public.unaccent', $1);
            $$ LANGUAGE sql IMMUTABLE PARALLEL SAFE STRICT;
        SQL
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql(
            <<<'SQL'
            ALTER TABLE sites DROP CONSTRAINT IF EXISTS chk_chronology ;
       SQL
        );

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

        // Drop triggers and functions for sample_stratigraphic_units
        $this->addSql(
            <<<'SQL'
        DROP TRIGGER IF EXISTS trg_enforce_sample_stratigraphic_unit_site_consistency ON sample_stratigraphic_units;
        SQL
        );
        $this->addSql(
            <<<'SQL'
        DROP FUNCTION IF EXISTS validate_sample_stratigraphic_units_site;
        SQL
        );

        $this->addSql(
            <<<'SQL'
        DROP TRIGGER IF EXISTS trg_sample_reference_exclusivity ON samples;
        SQL
        );

        $this->addSql(
            <<<'SQL'
        DROP FUNCTION IF EXISTS enforce_samples_reference_exclusivity;
        SQL
        );

        $this->addSql(
            <<<'SQL'
        DROP TRIGGER IF EXISTS trg_set_sample_site_id ON samples;
        SQL
        );

        $this->addSql(
            <<<'SQL'
            DROP FUNCTION IF EXISTS unaccent_immutable;
        SQL
        );
    }
}
