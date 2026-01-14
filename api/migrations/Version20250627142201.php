<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250627142201 extends AbstractMigration
{
    public const array TABLES = [
        'analysis_botany_charcoals' => ['analysisBotanyCharcoal', 'botanyCharcoal'],
        'analysis_botany_seeds' => ['analysisBotanySeed', 'botanySeed'],
        'analysis_contexts_botany' => ['analysisContextBotany', 'context'],
        'analysis_contexts_zoo' => ['analysisContextZoo', 'context'],
        'analysis_individuals' => ['analysisIndividual', 'individual'],
        'analysis_potteries' => ['analysisPottery', 'pottery'],
        'analysis_samples_microstratigraphy' => ['analysisSampleMicrostratigraphy', 'sample'],
        'analysis_sites_anthropology' => ['analysisSiteAnthropology', 'site'],
        'analysis_zoo_bones' => ['analysisZooBone', 'zooBone'],
        'analysis_zoo_teeth' => ['analysisZooTooth', 'zooTooth'],
    ];

    public function getDescription(): string
    {
        return 'Create analyses subject join view "vw_analysis_subjects"';
    }

    private function generateUnionViewSelectChunk(string $subjectTableName, string $joinResourceName, string $subjectResourceName): string
    {
        return <<<SQL
                    SELECT
                        aj.id,
                        aj.subject_id,
                        '$joinResourceName' as join_resource_name,
                        '$subjectResourceName' as resource_name,
                        aj.analysis_id
                    FROM $subjectTableName aj
                    LEFT JOIN analyses a ON aj.analysis_id = a.id
            SQL;
    }

    private function generateUnionView(): string
    {
        foreach (self::TABLES as $analysisTableName => $resourceNames) {
            $chunks[] = $this->generateUnionViewSelectChunk($analysisTableName, $resourceNames[0], $resourceNames[1]);
        }
        $query = implode(" UNION \n", $chunks);

        return <<<SQL
                    CREATE OR REPLACE VIEW vw_analysis_subjects AS
                    $query
                    ORDER BY id
            SQL;
    }

    public function up(Schema $schema): void
    {
        $this->addSql($this->generateUnionView());
    }

    public function down(Schema $schema): void
    {
        $this->addSql(
            <<<SQL
                    DROP VIEW IF EXISTS vw_analysis_subjects
            SQL
        );
    }
}
