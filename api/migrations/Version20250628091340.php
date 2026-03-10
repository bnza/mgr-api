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
                                    DISTINCT field_director as original_value FROM archaeological_sites
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

        $this->addSql(
            <<<'SQL'
                CREATE OR REPLACE VIEW vw_archaeological_sites AS
                SELECT
                    s.id,
                    s.code,
                    s.name,
                    s.description,
                    s.chronology_lower,
                    s.chronology_upper,
                    s.field_director,
                    r.value AS region,
                    s.the_geom
                FROM archaeological_sites s
                JOIN vocabulary.regions r ON s.region_id = r.id;
            SQL
        );

        $this->addSql(
            <<<'SQL'
                CREATE OR REPLACE VIEW vw_sampling_sites AS
                SELECT
                    s.id,
                    s.code,
                    s.name,
                    s.description,
                    r.value AS region,
                    s.the_geom
                FROM sampling_sites s
                JOIN vocabulary.regions r ON s.region_id = r.id;
            SQL
        );

        $this->addSql(
            <<<'SQL'
                CREATE OR REPLACE VIEW vocabulary.vw_history_locations AS
                SELECT
                    l.id,
                    l.value,
                    r.value AS region,
                    l.the_geom
                FROM vocabulary.history_locations l
                JOIN vocabulary.regions r ON l.region_id = r.id;
            SQL
        );

        $this->addSql(
            <<<'SQL'
                CREATE OR REPLACE VIEW vw_potteries AS
                SELECT
                    p.id, p.inventory, p.inner_color, p.outer_color, p.decoration_motif,
                    p.chronology_lower, p.chronology_upper, p.notes,
                    p.surface_treatment_id, p.cultural_context_id, p.part_id AS shape_id,
                    p.functional_group_id, p.functional_form_id,
                    su.site_id, s.code AS site_code, s.name AS site_name, s.the_geom
                FROM potteries p
                JOIN sus su ON p.stratigraphic_unit_id = su.id
                JOIN archaeological_sites s ON su.site_id = s.id;
            SQL
        );

        $this->addSql(
            <<<'SQL'
                CREATE OR REPLACE VIEW vw_individuals AS
                SELECT
                    i.id, i.identifier, i.sex, i.notes, i.age_id,
                    su.site_id, s.code AS site_code, s.name AS site_name, s.the_geom
                FROM individuals i
                JOIN sus su ON i.stratigraphic_unit_id = su.id
                JOIN archaeological_sites s ON su.site_id = s.id;
            SQL
        );

        $this->addSql(
            <<<'SQL'
                CREATE OR REPLACE VIEW vw_mus AS
                SELECT
                    m.id, m.identifier, m.notes,
                    su.site_id, s.code AS site_code, s.name AS site_name, s.the_geom
                FROM mus m
                JOIN sus su ON m.stratigraphic_unit_id = su.id
                JOIN archaeological_sites s ON su.site_id = s.id;
            SQL
        );

        $this->addSql(
            <<<'SQL'
                CREATE OR REPLACE VIEW vw_zoo_bones AS
                SELECT
                    b.id, b.ends_preserved, b.side, b.notes,
                    b.voc_taxonomy_id AS taxonomy_id, b.voc_bone_id AS element_id, b.voc_bone_part_id AS part_id,
                    su.site_id, s.code AS site_code, s.name AS site_name, s.the_geom
                FROM zoo_bones b
                JOIN sus su ON b.stratigraphic_unit_id = su.id
                JOIN archaeological_sites s ON su.site_id = s.id;
            SQL
        );

        $this->addSql(
            <<<'SQL'
                CREATE OR REPLACE VIEW vw_zoo_teeth AS
                SELECT
                    t.id, t.connected, t.side, t.notes,
                    t.voc_taxonomy_id AS taxonomy_id, t.voc_tooth_id AS element_id,
                    su.site_id, s.code AS site_code, s.name AS site_name, s.the_geom
                FROM zoo_teeth t
                JOIN sus su ON t.stratigraphic_unit_id = su.id
                JOIN archaeological_sites s ON su.site_id = s.id;
            SQL
        );

        $this->addSql(
            <<<'SQL'
                CREATE OR REPLACE VIEW vw_botany_charcoals AS
                SELECT
                    c.id, c.notes,
                    c.voc_taxonomy_id AS taxonomy_id, c.voc_element_id AS element_id, c.voc_element_part_id AS part_id,
                    su.site_id, s.code AS site_code, s.name AS site_name, s.the_geom
                FROM botany_charcoals c
                JOIN sus su ON c.stratigraphic_unit_id = su.id
                JOIN archaeological_sites s ON su.site_id = s.id;
            SQL
        );

        $this->addSql(
            <<<'SQL'
                CREATE OR REPLACE VIEW vw_botany_seeds AS
                SELECT
                    s_seed.id, s_seed.notes,
                    s_seed.voc_taxonomy_id AS taxonomy_id, s_seed.voc_element_id AS element_id, s_seed.voc_element_part_id AS part_id,
                    su.site_id, s.code AS site_code, s.name AS site_name, s.the_geom
                FROM botany_seeds s_seed
                JOIN sus su ON s_seed.stratigraphic_unit_id = su.id
                JOIN archaeological_sites s ON su.site_id = s.id;
            SQL
        );

        $this->addSql(
            <<<'SQL'
                CREATE OR REPLACE VIEW vw_history_animals AS
                SELECT
                    a.id, a.chronology_lower, a.chronology_upper, a.reference, a.notes,
                    a.animal_id, a.location_id,
                    l.value AS location_value, r.value AS region, l.the_geom
                FROM history_animals a
                JOIN vocabulary.history_locations l ON a.location_id = l.id
                JOIN vocabulary.regions r ON l.region_id = r.id;
            SQL
        );

        $this->addSql(
            <<<'SQL'
                CREATE OR REPLACE VIEW vw_history_plants AS
                SELECT
                    p.id, p.chronology_lower, p.chronology_upper, p.reference, p.notes,
                    p.plant_id, p.location_id,
                    l.value AS location_value, r.value AS region, l.the_geom
                FROM history_plants p
                JOIN vocabulary.history_locations l ON p.location_id = l.id
                JOIN vocabulary.regions r ON l.region_id = r.id;
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

        $this->addSql('DROP VIEW IF EXISTS vw_archaeological_sites;');
        $this->addSql('DROP VIEW IF EXISTS vw_sampling_sites;');
        $this->addSql('DROP VIEW IF EXISTS vocabulary.vw_history_locations;');
        $this->addSql('DROP VIEW IF EXISTS vw_potteries;');
        $this->addSql('DROP VIEW IF EXISTS vw_individuals;');
        $this->addSql('DROP VIEW IF EXISTS vw_mus;');
        $this->addSql('DROP VIEW IF EXISTS vw_zoo_bones;');
        $this->addSql('DROP VIEW IF EXISTS vw_zoo_teeth;');
        $this->addSql('DROP VIEW IF EXISTS vw_botany_charcoals;');
        $this->addSql('DROP VIEW IF EXISTS vw_botany_seeds;');
        $this->addSql('DROP VIEW IF EXISTS vw_history_animals;');
        $this->addSql('DROP VIEW IF EXISTS vw_history_plants;');
    }
}
