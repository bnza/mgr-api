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
            ALTER TABLE analyses
            ADD CONSTRAINT chk_at_least_one_reference
            CHECK (
                su_id IS NOT NULL OR
                context_id IS NOT NULL OR
                sample_id IS NOT NULL
            );
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

        $this->addSql(
            <<<'SQL'
            CREATE OR REPLACE FUNCTION enforce_sample_reference_exclusivity()
            RETURNS TRIGGER AS $$
            BEGIN
                -- Skip for INSERT (only enforce on UPDATE)
                IF TG_OP = 'INSERT' THEN
                    RETURN NEW;
                END IF;

                -- Check if either field was modified
                IF (NEW.su_id IS DISTINCT FROM OLD.su_id) OR (NEW.context_id IS DISTINCT FROM OLD.context_id) THEN

                    -- Case 1: su_id was changed (to NULL or non-NULL)
                    IF NEW.su_id IS DISTINCT FROM OLD.su_id THEN
                        IF NEW.su_id IS NOT NULL THEN
                            RAISE NOTICE 'Setting su_id to %, nullifying context_id', NEW.su_id;
                            NEW.context_id := NULL;
                        ELSE
                            RAISE NOTICE 'su_id set to NULL, keeping context_id as %', NEW.context_id;
                        END IF;

                    -- Case 2: Only context_id was changed (su_id unchanged)
                    ELSIF NEW.context_id IS DISTINCT FROM OLD.context_id THEN
                        IF NEW.context_id IS NOT NULL THEN
                            RAISE NOTICE 'Setting context_id to %, nullifying su_id', NEW.context_id;
                            NEW.su_id := NULL;
                        ELSE
                            RAISE NOTICE 'context_id set to NULL, keeping su_id as %', NEW.su_id;
                        END IF;
                    END IF;

                END IF;

                RETURN NEW;
            END;
            $$ LANGUAGE plpgsql;

        SQL
        );

        $this->addSql(
            <<<'SQL'
            ALTER TABLE samples
            ADD CONSTRAINT chk_exclusive_references
            CHECK (
                -- Exactly one of su_id or context_id must be set (mutually exclusive)
                (su_id IS NOT NULL AND context_id IS NULL) OR
                (context_id IS NOT NULL AND su_id IS NULL)
            );
        SQL
        );
        $this->addSql(
            <<<'SQL'
            CREATE TRIGGER trg_sample_reference_exclusivity
            BEFORE UPDATE ON samples
            FOR EACH ROW EXECUTE FUNCTION enforce_sample_reference_exclusivity();
        SQL
        );

        $this->addSql(
            <<<'SQL'
            CREATE OR REPLACE FUNCTION get_sample_site_id(su_id BIGINT, context_id BIGINT)
            RETURNS BIGINT AS $$
            BEGIN
                IF su_id IS NOT NULL THEN
                    RETURN (SELECT site_id FROM sus WHERE id = su_id);
                ELSIF context_id IS NOT NULL THEN
                    RETURN (SELECT site_id FROM contexts WHERE id = context_id);
                END IF;
                RETURN NULL;
            END;
            $$ LANGUAGE plpgsql;
        SQL
        );

        $this->addSql(
            <<<'SQL'
            CREATE OR REPLACE FUNCTION update_sample_site_id()
            RETURNS TRIGGER AS $$
            BEGIN
                NEW.site_id := get_sample_site_id(NEW.su_id, NEW.context_id);
                RETURN NEW;
            END;
            $$ LANGUAGE plpgsql;
        SQL
        );

        $this->addSql(
            <<<'SQL'
            CREATE TRIGGER trg_set_sample_site_id
                BEFORE INSERT OR UPDATE ON samples
                FOR EACH ROW
                EXECUTE FUNCTION update_sample_site_id();
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
        DROP FUNCTION IF EXISTS update_sample_site_id;
        SQL
        );

        $this->addSql(
            <<<'SQL'
        DROP FUNCTION IF EXISTS get_sample_site_id;
        SQL
        );

        $this->addSql(
            <<<'SQL'
            ALTER TABLE samples
            DROP CONSTRAINT chk_exclusive_references;
        SQL
        );

        $this->addSql(
            <<<'SQL'
            ALTER TABLE analyses
            DROP CONSTRAINT chk_at_least_one_reference;
        SQL
        );

        $this->addSql(
            <<<'SQL'
            DROP FUNCTION IF EXISTS unaccent_immutable;
        SQL
        );
    }
}
