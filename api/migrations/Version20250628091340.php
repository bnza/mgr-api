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
            DROP VIEW vw_stratigraphic_units_relationships;
SQL
        );
    }
}
