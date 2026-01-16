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
                     ALTER TABLE sediment_core_depths ADD CONSTRAINT chk_chronology CHECK (depth_min IS NULL OR depth_max IS NULL OR depth_max > depth_min);
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

        // Enforce: sediment_core_stratigraphic_units.sample_id site == sus.site_id
        $this->addSql(
            <<<'SQL'
                    CREATE OR REPLACE FUNCTION validate_sediment_core_stratigraphic_units_site()
                    RETURNS TRIGGER AS $$
                    BEGIN
                        IF (SELECT site_id FROM sediment_cores WHERE id = NEW.sediment_core_id) !=
                           (SELECT site_id FROM sus     WHERE id = NEW.su_id) THEN
                            RAISE EXCEPTION 'Sediment core and stratigraphic unit must belong to the same site';
                        END IF;
                        RETURN NEW;
                    END;
                    $$ LANGUAGE plpgsql;
                SQL
        );

        $this->addSql(
            <<<'SQL'
                    CREATE TRIGGER validate_sediment_core_stratigraphic_units_site
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

        // Enforce: when type_group = 'absolute dating', id must be between 100 and 199 (inclusive)
        $this->addSql(
            <<<'SQL'
                    ALTER TABLE vocabulary.analysis_types
                    ADD CONSTRAINT chk_analysis_types_absdating_id_range
                    CHECK (type_group <> 'absolute dating' OR (id >= 100 AND id <= 199));
                SQL
        );

        $this->addSql(
            <<<'SQL'
                    COMMENT ON CONSTRAINT chk_analysis_types_absdating_id_range ON vocabulary.analysis_types
                    IS 'If type_group = ''absolute dating'', then id must be between 100 and 199 inclusive';
                SQL
        );

        // Enforce: pottery.inventory must be unique within the same site
        $this->addSql(
            <<<'SQL'
                    CREATE OR REPLACE FUNCTION validate_pottery_inventory_site_uniqueness()
                    RETURNS TRIGGER AS $$
                    BEGIN
                        IF EXISTS (
                            SELECT 1
                            FROM potteries p
                            JOIN sus s ON p.stratigraphic_unit_id = s.id
                            WHERE p.inventory = NEW.inventory
                              AND p.id != NEW.id
                              AND s.site_id = (SELECT site_id FROM sus WHERE id = NEW.stratigraphic_unit_id)
                        ) THEN
                            RAISE EXCEPTION 'Pottery inventory % must be unique within the same site', NEW.inventory;
                        END IF;
                        RETURN NEW;
                    END;
                    $$ LANGUAGE plpgsql;
                SQL
        );

        $this->addSql(
            <<<'SQL'
                    CREATE TRIGGER trg_enforce_pottery_inventory_site_uniqueness
                    BEFORE INSERT OR UPDATE ON potteries
                    FOR EACH ROW EXECUTE FUNCTION validate_pottery_inventory_site_uniqueness();
                SQL
        );

        // Enforce: individual.identifier must be unique within the same site
        $this->addSql(
            <<<'SQL'
                    CREATE OR REPLACE FUNCTION validate_individual_identifier_site_uniqueness()
                    RETURNS TRIGGER AS $$
                    BEGIN
                        IF EXISTS (
                            SELECT 1
                            FROM individuals i
                            JOIN sus s ON i.stratigraphic_unit_id = s.id
                            WHERE i.identifier = NEW.identifier
                              AND i.id != NEW.id
                              AND s.site_id = (SELECT site_id FROM sus WHERE id = NEW.stratigraphic_unit_id)
                        ) THEN
                            RAISE EXCEPTION 'Individual identifier % must be unique within the same site', NEW.identifier;
                        END IF;
                        RETURN NEW;
                    END;
                    $$ LANGUAGE plpgsql;
                SQL
        );

        $this->addSql(
            <<<'SQL'
                    CREATE TRIGGER trg_enforce_individual_identifier_site_uniqueness
                    BEFORE INSERT OR UPDATE ON individuals
                    FOR EACH ROW EXECUTE FUNCTION validate_individual_identifier_site_uniqueness();
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
                ALTER TABLE vocabulary.analysis_types DROP CONSTRAINT IF EXISTS chk_analysis_types_absdating_id_range;
                SQL
        );

        $this->addSql(
            <<<'SQL'
                    DROP FUNCTION IF EXISTS unaccent_immutable;
                SQL
        );

        $this->addSql(
            <<<'SQL'
                    DROP TRIGGER IF EXISTS trg_enforce_pottery_inventory_site_uniqueness ON potteries;
                SQL
        );

        $this->addSql(
            <<<'SQL'
                    DROP FUNCTION IF EXISTS validate_pottery_inventory_site_uniqueness;
                SQL
        );

        $this->addSql(
            <<<'SQL'
                    DROP TRIGGER IF EXISTS trg_enforce_individual_identifier_site_uniqueness ON potteries;
                SQL
        );

        $this->addSql(
            <<<'SQL'
                    DROP FUNCTION IF EXISTS validate_individual_identifier_site_uniqueness;
                SQL
        );
    }
}
