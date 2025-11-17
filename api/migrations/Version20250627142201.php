<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Symfony\Component\String\Inflector\EnglishInflector;
use Symfony\Component\String\UnicodeString;

final class Version20250627142201 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Set absolute dating analysis join trigger checks';
    }

    public const array TABLES = [
        'analysis_botany_charcoals',
        'analysis_botany_seeds',
        'analysis_individuals',
        'analysis_potteries',
        'analysis_zoo_bones',
        'analysis_zoo_teeth',
    ];

    private function getAbsDatingTableName(string $analysisTableName): string
    {
        return "abs_dating_$analysisTableName";
    }

    private function getValidateFunctionName(string $analysisTableName): string
    {
        return "validate_{$this->getAbsDatingTableName($analysisTableName)}_group";
    }

    private function getValidateTriggerName(string $analysisTableName): string
    {
        return "trg_{$this->getAbsDatingTableName($analysisTableName)}_enforce_group";
    }

    private function getValidateFunctionBody(string $analysisTableName): string
    {
        $absDatingTableName = $this->getAbsDatingTableName($analysisTableName);
        $sql = <<<SQL
        -- 1) Validate on child INSERT/UPDATE
        CREATE OR REPLACE FUNCTION {$this->getValidateFunctionName($analysisTableName)}()
        RETURNS TRIGGER AS $$
        DECLARE v_group text;
        BEGIN
            SELECT at.type_group INTO v_group
            FROM {$analysisTableName} aj
            JOIN analyses a ON aj.analysis_id = a.id
            JOIN vocabulary.analysis_types at ON at.id = a.analysis_type_id
            WHERE aj.id = NEW.id;

            IF v_group IS NULL THEN
                RAISE EXCEPTION 'Referenced {$analysisTableName} identifier "%" not found', NEW.id;
            END IF;

            IF v_group <> 'absolute dating' THEN
                RAISE EXCEPTION '{$absDatingTableName}.id "%" must reference an analysis with group = ''absolute dating'' (found %)', NEW.id, v_group;
            END IF;

            RETURN NEW;
        END;
        $$ LANGUAGE plpgsql;
SQL;

        return $sql;
    }

    private function getEnforceGroupTriggerBody(string $analysisTableName): string
    {
        $absDatingTableName = $this->getAbsDatingTableName($analysisTableName);
        $sql = <<<SQL
        CREATE TRIGGER {$this->getValidateTriggerName($analysisTableName)}
        BEFORE INSERT OR UPDATE ON {$absDatingTableName}
        FOR EACH ROW EXECUTE FUNCTION {$this->getValidateFunctionName($analysisTableName)}()
SQL;

        return $sql;
    }

    private function getEnforceGroupTriggerComment(string $analysisTableName): string
    {
        $absDatingTableName = $this->getAbsDatingTableName($analysisTableName);
        $sql = <<<SQL
        COMMENT ON TRIGGER {$this->getValidateTriggerName($analysisTableName)} ON {$absDatingTableName}
        IS 'Enforce: {$absDatingTableName}.id analysis_id group = ''absolute dating''';
SQL;

        return $sql;
    }

    private function getEnforceFunctionName(string $analysisTableName): string
    {
        return "prevent_{$analysisTableName}_group_change_if_abs_child";
    }

    private function getEnforceTriggerName(string $analysisTableName): string
    {
        return "trg_{$analysisTableName}_block_incompatible_group";
    }

    private function getEnforceAnalysisGroupFunctionBody(): string
    {
        // Build UNION query to check all absolute dating tables
        $unionParts = [];
        foreach (self::TABLES as $table) {
            $absDatingTable = $this->getAbsDatingTableName($table);
            $unionParts[] = "SELECT 1 FROM $table aj LEFT JOIN $absDatingTable abs ON aj.id = abs.id WHERE aj.analysis_id = NEW.id";
        }
        $existsQuery = implode(' UNION ', $unionParts);

        $sql = <<<SQL
        -- Prevent changing parent analysis to a non-absolute-dating type when a child row exists
        CREATE OR REPLACE FUNCTION prevent_analysis_group_change_if_abs_child()
        RETURNS TRIGGER AS $$
        DECLARE has_abs boolean;
        DECLARE v_group text;
        BEGIN
            -- If this analysis is extended by the abs-dating child, forbid switching it to a non-abs group
            SELECT EXISTS(
                {$existsQuery}
            ) INTO has_abs;

            IF has_abs THEN
                SELECT at.type_group INTO v_group
                FROM vocabulary.analysis_types at
                WHERE at.id = NEW.analysis_type_id;

                IF v_group <> 'absolute dating' THEN
                    RAISE EXCEPTION 'Cannot set analysis % to group % while an abs_dating child row exists', NEW.id, v_group;
                END IF;
            END IF;

            RETURN NEW;
        END;
        $$ LANGUAGE plpgsql;
SQL;

        return $sql;
    }

    private function getPreventAnalysisIdUpdateFunctionName(string $analysisTableName): string
    {
        return "prevent_{$analysisTableName}_analysis_id_update_if_abs_dating";
    }

    private function getPreventAnalysisIdUpdateTriggerName(string $analysisTableName): string
    {
        return "trg_{$analysisTableName}_block_analysis_id_update";
    }

    private function getPreventAnalysisIdUpdateFunctionBody(string $analysisTableName): string
    {
        $absDatingTableName = $this->getAbsDatingTableName($analysisTableName);
        $sql = <<<SQL
        -- Prevent updating analysis_id if the current analysis is 'absolute dating' and has an abs_dating child
        CREATE OR REPLACE FUNCTION {$this->getPreventAnalysisIdUpdateFunctionName($analysisTableName)}()
        RETURNS TRIGGER AS $$
        DECLARE v_group text;
        DECLARE has_abs_child boolean;
        BEGIN
            -- Only check on UPDATE when analysis_id is being changed
            IF TG_OP = 'UPDATE' AND OLD.analysis_id = NEW.analysis_id THEN
                RETURN NEW;
            END IF;

            -- Get the analysis group of the OLD analysis_id
            SELECT at.type_group INTO v_group
            FROM analyses a
            JOIN vocabulary.analysis_types at ON at.id = a.analysis_type_id
            WHERE a.id = OLD.analysis_id;

            -- If the old analysis is 'absolute dating', check if there's a child row
            IF v_group = 'absolute dating' THEN
                SELECT EXISTS(
                    SELECT 1 FROM {$absDatingTableName} WHERE id = OLD.id
                ) INTO has_abs_child;

                IF has_abs_child THEN
                    RAISE EXCEPTION 'Cannot update analysis_id in {$analysisTableName} (id=%) because it is linked to absolute dating analysis (%) and has a related {$absDatingTableName} entry', OLD.id, OLD.analysis_id;
                END IF;
            END IF;

            RETURN NEW;
        END;
        $$ LANGUAGE plpgsql;
SQL;

        return $sql;
    }

    private function getPreventAnalysisIdUpdateTriggerBody(string $analysisTableName): string
    {
        $sql = <<<SQL
        CREATE TRIGGER {$this->getPreventAnalysisIdUpdateTriggerName($analysisTableName)}
        BEFORE UPDATE ON {$analysisTableName}
        FOR EACH ROW EXECUTE FUNCTION {$this->getPreventAnalysisIdUpdateFunctionName($analysisTableName)}()
SQL;

        return $sql;
    }

    private function getPreventAnalysisIdUpdateTriggerComment(string $analysisTableName): string
    {
        $absDatingTableName = $this->getAbsDatingTableName($analysisTableName);
        $sql = <<<SQL
        COMMENT ON TRIGGER {$this->getPreventAnalysisIdUpdateTriggerName($analysisTableName)} ON {$analysisTableName}
        IS 'Prevent updating analysis_id when analysis group is ''absolute dating'' and {$absDatingTableName} entry exists';
SQL;

        return $sql;
    }

    private function generateUnionViewSelectChunk(string $analysisTableName, EnglishInflector $inflector): string
    {
        // strip 'analysis_' prefix from table name
        $subject_table = substr($analysisTableName, 9);
        $subjectType = new UnicodeString(
            'zoo_bones' === $subject_table
                ? 'zoo_bone'
                : $inflector->singularize($subject_table)[0]
        )->camel();

        $sql = <<<SQL
        SELECT
            aj.id,
            aj.subject_id,
            '$subjectType' as subject_type,
            s.stratigraphic_unit_id,
            aj.analysis_id,
            abs.dating_lower,
            abs.dating_upper,
            abs.uncalibrated_dating,
            abs.error,
            abs.calibration_curve,
            abs.notes
        FROM analysis_{$subject_table} aj
        LEFT JOIN analyses a ON aj.analysis_id = a.id
        LEFT JOIN abs_dating_analysis_{$subject_table} abs ON aj.id = abs.id
        JOIN {$subject_table} s ON aj.subject_id = s.id
        WHERE a.analysis_type_id < 200
SQL;

        return $sql;
    }

    private function generateUnionView(): string
    {
        $inflector = new EnglishInflector();
        $chunks = [];
        foreach (self::TABLES as $analysisTableName) {
            $chunks[] = $this->generateUnionViewSelectChunk($analysisTableName, $inflector);
        }
        $query = implode(" UNION \n", $chunks);
        $sql = <<<SQL
        CREATE OR REPLACE VIEW vw_abs_dating_analyses AS
        $query;
SQL;

        return $sql;
    }

    public function up(Schema $schema): void
    {
        $this->addSql($this->generateUnionView());
        foreach (self::TABLES as $analysisTableName) {
            $this->addSql($this->getValidateFunctionBody($analysisTableName));
            $this->addSql($this->getEnforceGroupTriggerBody($analysisTableName));
            $this->addSql($this->getEnforceGroupTriggerComment($analysisTableName));
            $this->addSql($this->getPreventAnalysisIdUpdateFunctionBody($analysisTableName));
            $this->addSql($this->getPreventAnalysisIdUpdateTriggerBody($analysisTableName));
            $this->addSql($this->getPreventAnalysisIdUpdateTriggerComment($analysisTableName));
        }
        $this->addSql($this->getEnforceAnalysisGroupFunctionBody());
        $this->addSql(<<<SQL
        CREATE TRIGGER trg_analysis_block_incompatible_group
        BEFORE UPDATE ON analyses
        FOR EACH ROW EXECUTE FUNCTION prevent_analysis_group_change_if_abs_child();
SQL
        );
        $this->addSql(
            <<<SQL
        COMMENT ON TRIGGER trg_analysis_block_incompatible_group ON analyses
        IS 'Prevent changing analysis group to a non-absolute-dating type when a child row exists';
SQL
        );
    }

    public function down(Schema $schema): void
    {
        foreach (self::TABLES as $analysisTableName) {
            $this->addSql('DROP TRIGGER IF EXISTS '.$this->getValidateTriggerName($analysisTableName).' ON '.$this->getAbsDatingTableName($analysisTableName));
            $this->addSql('DROP FUNCTION IF EXISTS '.$this->getValidateFunctionName($analysisTableName));
            $this->addSql('DROP TRIGGER IF EXISTS '.$this->getPreventAnalysisIdUpdateTriggerName($analysisTableName).' ON '.$analysisTableName);
            $this->addSql('DROP FUNCTION IF EXISTS '.$this->getPreventAnalysisIdUpdateFunctionName($analysisTableName));
        }
        $this->addSql('DROP TRIGGER IF EXISTS trg_analysis_block_incompatible_group ON analyses');
        $this->addSql('DROP FUNCTION IF EXISTS prevent_analysis_group_change_if_abs_child');
        $this->addSql('DROP VIEW IF EXISTS vw_abs_dating_analyses');
    }
}
