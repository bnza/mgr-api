<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260323152247 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create `geoserver` views';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE SCHEMA IF NOT EXISTS geoserver;');
        $this->addSql(
            <<<'SQL'
                CREATE OR REPLACE VIEW geoserver.vw_archaeological_sites AS
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
                CREATE OR REPLACE VIEW geoserver.vw_sampling_sites AS
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
                CREATE OR REPLACE VIEW geoserver.vw_history_locations AS
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
                CREATE OR REPLACE VIEW geoserver.vw_potteries AS
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
                CREATE OR REPLACE VIEW geoserver.vw_individuals AS
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
                CREATE OR REPLACE VIEW geoserver.vw_mus AS
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
                CREATE OR REPLACE VIEW geoserver.vw_zoo_bones AS
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
                CREATE OR REPLACE VIEW geoserver.vw_zoo_teeth AS
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
                CREATE OR REPLACE VIEW geoserver.vw_botany_charcoals AS
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
                CREATE OR REPLACE VIEW geoserver.vw_botany_seeds AS
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
                CREATE OR REPLACE VIEW geoserver.vw_paleoclimate_sampling_sites AS
                SELECT
                    s.id,
                    s.code,
                    s.name,
                    s.description,
                    r.value AS region,
                    s.the_geom
                FROM paleoclimate_sampling_sites s
                JOIN vocabulary.regions r ON s.region_id = r.id;
            SQL
        );

        $this->addSql(
            <<<'SQL'
                CREATE OR REPLACE VIEW geoserver.vw_paleoclimate_samples AS
                SELECT
                    p.id, s.code || '.' || p.number AS code, p.number, p.description,
                    p.chronology_lower, p.chronology_upper, p.length,
                    p.temperature_record, p.precipitation_record,
                    p.stable_isotopes, p.trace_elements,
                    p.petrographic_descriptions, p.fluid_inclusions,
                    p.site_id, s.code AS site_code, s.name AS site_name, s.the_geom
                FROM paleoclimate_sample p
                JOIN paleoclimate_sampling_sites s ON p.site_id = s.id;
            SQL
        );

        $this->addSql(
            <<<'SQL'
                CREATE OR REPLACE VIEW geoserver.vw_history_animals AS
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
                CREATE OR REPLACE VIEW geoserver.vw_history_plants AS
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
        $this->addSql('DROP SCHEMA IF EXISTS geoserver CASCADE;');
    }
}
