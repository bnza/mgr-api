<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250628091340 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create views';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            <<<'SQL'
                            CREATE OR REPLACE VIEW vocabulary.vw_botany_taxonomy_classes AS
                            WITH DistinctValues AS (
                                -- Step 1: Find the unique, input values.
                                SELECT DISTINCT class AS original_value
                                FROM vocabulary.botany_taxonomy
                                WHERE class IS NOT NULL
                            )
                            -- Step 2: Calculate the MD5 hash once for each unique type.
                            SELECT
                                MD5(original_value) AS id,
                                original_value AS value
                            FROM
                                DistinctValues
                SQL
        );

        $this->addSql(
            <<<'SQL'
                            CREATE OR REPLACE VIEW vocabulary.vw_botany_taxonomy_families AS
                            WITH DistinctValues AS (
                                -- Step 1: Find the unique, input values.
                                SELECT DISTINCT family AS original_value
                                FROM vocabulary.botany_taxonomy
                                WHERE family IS NOT NULL
                            )
                            -- Step 2: Calculate the MD5 hash once for each unique type.
                            SELECT
                                MD5(original_value) AS id,
                                original_value AS value
                            FROM
                                DistinctValues
                SQL
        );

        $this->addSql(
            <<<'SQL'
                            CREATE OR REPLACE VIEW vocabulary.vw_zoo_taxonomy_classes AS
                            WITH DistinctValues AS (
                                -- Step 1: Find the unique, input values.
                                SELECT DISTINCT class AS original_value
                                FROM vocabulary.zoo_taxonomy
                                WHERE class IS NOT NULL
                            )
                            -- Step 2: Calculate the MD5 hash once for each unique type.
                            SELECT
                                MD5(original_value) AS id,
                                original_value AS value
                            FROM
                                DistinctValues
                SQL
        );

        $this->addSql(
            <<<'SQL'
                            CREATE OR REPLACE VIEW vocabulary.vw_zoo_taxonomy_families AS
                            WITH DistinctValues AS (
                                -- Step 1: Find the unique, input values.
                                SELECT DISTINCT family AS original_value
                                FROM vocabulary.zoo_taxonomy
                                WHERE family IS NOT NULL
                            )
                            -- Step 2: Calculate the MD5 hash once for each unique type.
                            SELECT
                                MD5(original_value) AS id,
                                original_value AS value
                            FROM
                                DistinctValues
                SQL
        );

        $this->addSql(
            <<<'SQL'
                            CREATE OR REPLACE VIEW vw_areas AS
                            WITH DistinctValues AS (
                                SELECT DISTINCT sus.site_id AS site_id, sus.area AS original_value
                                FROM sus
                                WHERE sus.area IS NOT NULL
                            )
                            -- Step 2: Calculate the MD5 hash once for each unique type.
                            SELECT
                                MD5(DistinctValues.site_id || original_value) AS id,
                                site_id,
                                original_value AS value
                            FROM
                                DistinctValues
                SQL
        );

        $this->addSql(
            <<<'SQL'
                            CREATE OR REPLACE VIEW vw_buildings AS
                            WITH DistinctValues AS (
                                -- Step 1: Find the unique, input values.
                                SELECT DISTINCT sus.site_id, sus.area, sus.building
                                FROM sus
                                WHERE sus.building IS NOT NULL
                            )
                            -- Step 2: Calculate the MD5 hash once for each unique type.
                            SELECT
                                MD5(DistinctValues.site_id ||DistinctValues.area || DistinctValues.building) AS id,
                                site_id,
                                area,
                                building AS value
                            FROM
                                DistinctValues
                SQL
        );

        $this->addSql(
            <<<'SQL'
                            CREATE OR REPLACE VIEW vw_context_types AS
                            WITH DistinctValues AS (
                                -- Step 1: Find the unique, input values.
                                SELECT DISTINCT LOWER(type) AS original_value
                                FROM contexts
                            )
                            -- Step 2: Calculate the MD5 hash once for each unique type.
                            SELECT
                                MD5(original_value) AS id,
                                original_value AS value
                            FROM
                                DistinctValues
                SQL
        );

        $this->addSql(
            <<<'SQL'
                            CREATE VIEW vw_analysis_laboratories AS
                            WITH DistinctValues AS (
                                -- Step 1: Find the unique, input values.
                                SELECT
                                DISTINCT laboratory AS original_value FROM analyses
                                WHERE laboratory IS NOT NULL
                            )
                            -- Step 2: Calculate the MD5 hash once for each unique type.
                            SELECT
                                MD5(original_value) AS id,
                                original_value AS value
                            FROM
                                DistinctValues
                SQL
        );

        $this->addSql(
            <<<'SQL'
                            CREATE VIEW vw_history_references AS
                            WITH DistinctValues AS (
                                -- Step 1: Find the unique, input values.
                                SELECT
                                DISTINCT reference AS original_value FROM history_plants
                                WHERE reference IS NOT NULL
                            )
                            -- Step 2: Calculate the MD5 hash once for each unique type.
                            SELECT
                                MD5(original_value) AS id,
                                original_value AS value
                            FROM
                                DistinctValues
                SQL
        );

        $this->addSql(
            <<<'SQL'
                            CREATE VIEW vw_persons AS
                            WITH DistinctValues AS (
                                -- Step 1: Find the unique, input values.
                                SELECT
                                    DISTINCT field_director as original_value FROM sites
                                    WHERE field_director IS NOT NULL
                                UNION
                                SELECT
                                    DISTINCT responsible as original_value FROM analyses
                                    WHERE responsible IS NOT NULL
                            )
                            -- Step 2: Calculate the MD5 hash once for each unique type.
                            SELECT
                                MD5(original_value) AS id,
                                original_value AS value
                            FROM
                                DistinctValues
                SQL
        );

        $this->addSql(
            <<<'SQL'
                            CREATE VIEW vw_calibration_curves AS
                            WITH DistinctValues AS (
                                -- Step 1: Find the unique, input values.
                                SELECT
                                    DISTINCT calibration_curve as original_value FROM vw_abs_dating_analyses
                                    WHERE calibration_curve IS NOT NULL
                                UNION
                                    SELECT 'N/D'::varchar as original_value
                            )
                            -- Step 2: Calculate the MD5 hash once for each unique type.
                            SELECT
                                MD5(original_value) AS id,
                                original_value AS value
                            FROM
                                DistinctValues
                SQL
        );

        $this->addSql(
            <<<'SQL'
                            CREATE VIEW vw_stratigraphic_units_relationships AS
                            SELECT
                            id, lft_su_id, relationship_id, rgt_su_id FROM stratigraphic_units_relationships
                            UNION
                            SELECT sr.id*-1, sr.rgt_su_id as lft_su_id, r.inverted_by_id, sr.lft_su_id as rgt_su_id FROM stratigraphic_units_relationships sr
                            LEFT JOIN vocabulary.su_relationships r ON sr.relationship_id::char = r.id::char;
                SQL
        );

        $this->addSql(
            <<<'SQL'
                    CREATE RULE vw_stratigraphic_units_relationships_insert_rule AS ON INSERT TO vw_stratigraphic_units_relationships DO INSTEAD
                    INSERT INTO stratigraphic_units_relationships
                    (
                        id,
                        lft_su_id,
                        rgt_su_id,
                        relationship_id
                    )
                    VALUES
                    (
                        nextval('stratigraphic_units_relationships_id_seq'),
                        NEW.lft_su_id,
                        NEW.rgt_su_id,
                        NEW.relationship_id
                    )
                SQL
        );

        $this->addSql(
            <<<'SQL'
                    CREATE RULE vw_stratigraphic_units_relationships_delete_rule AS ON DELETE TO vw_stratigraphic_units_relationships DO INSTEAD
                    DELETE FROM stratigraphic_units_relationships WHERE id = ABS(OLD.id)
                SQL
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql(
            <<<'SQL'
                            DROP VIEW vocabulary.vw_botany_taxonomy_classes;
                SQL
        );

        $this->addSql(
            <<<'SQL'
                            DROP VIEW vocabulary.vw_botany_taxonomy_families;
                SQL
        );

        $this->addSql(
            <<<'SQL'
                            DROP VIEW vocabulary.vw_zoo_taxonomy_classes;
                SQL
        );

        $this->addSql(
            <<<'SQL'
                            DROP VIEW vocabulary.vw_zoo_taxonomy_families;
                SQL
        );

        $this->addSql(
            <<<'SQL'
                            DROP VIEW vw_analysis_laboratories;
                SQL
        );

        $this->addSql(
            <<<'SQL'
                            DROP VIEW vw_areas;
                SQL
        );

        $this->addSql(
            <<<'SQL'
                            DROP VIEW vw_buildings;
                SQL
        );

        $this->addSql(
            <<<'SQL'
                            DROP VIEW vw_calibration_curves;
                SQL
        );

        $this->addSql(
            <<<'SQL'
                            DROP VIEW vw_context_types;
                SQL
        );

        $this->addSql(
            <<<'SQL'
                            DROP VIEW vw_history_references;
                SQL
        );

        $this->addSql(
            <<<'SQL'
                            DROP VIEW vw_persons;
                SQL
        );

        $this->addSql(
            <<<'SQL'
                            DROP VIEW vw_stratigraphic_units_relationships;
                SQL
        );
    }
}
