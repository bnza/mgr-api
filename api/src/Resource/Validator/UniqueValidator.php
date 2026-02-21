<?php

namespace App\Resource\Validator;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use App\Entity\Auth\SiteUserPrivilege;
use App\Entity\Data\Analysis;
use App\Entity\Data\ArchaeologicalSite;
use App\Entity\Data\Context;
use App\Entity\Data\Individual;
use App\Entity\Data\Join\Analysis\AnalysisBotanyCharcoal;
use App\Entity\Data\Join\Analysis\AnalysisBotanySeed;
use App\Entity\Data\Join\Analysis\AnalysisContextBotany;
use App\Entity\Data\Join\Analysis\AnalysisContextZoo;
use App\Entity\Data\Join\Analysis\AnalysisIndividual;
use App\Entity\Data\Join\Analysis\AnalysisPottery;
use App\Entity\Data\Join\Analysis\AnalysisSampleMicrostratigraphy;
use App\Entity\Data\Join\Analysis\AnalysisSiteAnthropology;
use App\Entity\Data\Join\Analysis\AnalysisZooBone;
use App\Entity\Data\Join\Analysis\AnalysisZooTooth;
use App\Entity\Data\Join\ContextStratigraphicUnit;
use App\Entity\Data\Join\MediaObject\MediaObjectAnalysis;
use App\Entity\Data\Join\MediaObject\MediaObjectStratigraphicUnit;
use App\Entity\Data\Join\SampleStratigraphicUnit;
use App\Entity\Data\Join\SedimentCoreDepth;
use App\Entity\Data\MediaObject;
use App\Entity\Data\MicrostratigraphicUnit;
use App\Entity\Data\Pottery;
use App\Entity\Data\Sample;
use App\Entity\Data\SedimentCore;
use App\Entity\Data\StratigraphicUnit;
use App\Entity\Data\View\StratigraphicUnitRelationshipView;
use App\Entity\Vocabulary\Botany\Taxonomy as VocBotanyTaxonomy;
use App\Entity\Vocabulary\History\Animal as VocHistoryAnimal;
use App\Entity\Vocabulary\History\Location as VocHistoryLocation;
use App\Entity\Vocabulary\History\Plant as VocHistoryPlant;
use App\Entity\Vocabulary\Zoo\Taxonomy as VocZooTaxonomy;
use App\State\ValidatorSiteRelatedUniqueProvider;
use App\State\ValidatorUniqueProvider;

#[ApiResource(
    operations: [
        new Get(
            uriTemplate: '/validator/unique/analyses/botany/charcoals',
            defaults: [
                'resource' => AnalysisBotanyCharcoal::class,
            ],
        ),
        new Get(
            uriTemplate: '/validator/unique/analyses/botany/seeds',
            defaults: [
                'resource' => AnalysisBotanySeed::class,
            ],
        ),
        new Get(
            uriTemplate: '/validator/unique/analyses/contexts/botany',
            defaults: [
                'resource' => AnalysisContextBotany::class,
            ],
        ),
        new Get(
            uriTemplate: '/validator/unique/analyses/contexts/zoo',
            defaults: [
                'resource' => AnalysisContextZoo::class,
            ],
        ),
        new Get(
            uriTemplate: '/validator/unique/analyses/individuals',
            defaults: [
                'resource' => AnalysisIndividual::class,
            ],
        ),
        new Get(
            uriTemplate: '/validator/unique/analyses/potteries',
            defaults: [
                'resource' => AnalysisPottery::class,
            ],
        ),
        new Get(
            uriTemplate: '/validator/unique/analyses/samples/microstratigraphy',
            defaults: [
                'resource' => AnalysisSampleMicrostratigraphy::class,
            ],
        ),
        new Get(
            uriTemplate: '/validator/unique/analyses/sites/anthropology',
            defaults: [
                'resource' => AnalysisSiteAnthropology::class,
            ],
        ),
        new Get(
            uriTemplate: '/validator/unique/analyses/zoo/bones',
            defaults: [
                'resource' => AnalysisZooBone::class,
            ],
        ),
        new Get(
            uriTemplate: '/validator/unique/analyses/zoo/teeth',
            defaults: [
                'resource' => AnalysisZooTooth::class,
            ],
        ),
        new Get(
            uriTemplate: '/validator/unique/analyses',
            defaults: [
                'resource' => Analysis::class,
            ],
        ),
        new Get(
            uriTemplate: '/validator/unique/context_stratigraphic_units',
            defaults: [
                'resource' => ContextStratigraphicUnit::class,
            ],
        ),
        new Get(
            uriTemplate: '/validator/unique/contexts',
            defaults: [
                'resource' => Context::class,
            ],
        ),
        new Get(
            uriTemplate: '/validator/unique/individuals',
            defaults: [
                'resource' => Individual::class,
            ],
            provider: ValidatorSiteRelatedUniqueProvider::class,
        ),
        new Get(
            uriTemplate: '/validator/unique/media_objects/analyses',
            defaults: [
                'resource' => MediaObjectAnalysis::class,
            ],
        ),
        new Get(
            uriTemplate: '/validator/unique/media_objects/sha256',
            defaults: [
                'resource' => MediaObject::class,
            ],
        ),
        new Get(
            uriTemplate: '/validator/unique/media_objects/stratigraphic_units',
            defaults: [
                'resource' => MediaObjectStratigraphicUnit::class,
            ],
        ),
        new Get(
            uriTemplate: '/validator/unique/microstratigraphic_units',
            defaults: [
                'resource' => MicrostratigraphicUnit::class,
            ],
        ),
        new Get(
            uriTemplate: '/validator/unique/potteries',
            defaults: [
                'resource' => Pottery::class,
            ],
            provider: ValidatorSiteRelatedUniqueProvider::class,
        ),
        new Get(
            uriTemplate: '/validator/unique/sample_stratigraphic_units',
            defaults: [
                'resource' => SampleStratigraphicUnit::class,
            ],
        ),
        new Get(
            uriTemplate: '/validator/unique/samples',
            defaults: [
                'resource' => Sample::class,
            ],
        ),
        new Get(
            uriTemplate: '/validator/unique/sediment_cores',
            defaults: [
                'resource' => SedimentCore::class,
            ],
        ),
        new Get(
            uriTemplate: '/validator/unique/sediment_core_depths',
            defaults: [
                'resource' => SedimentCoreDepth::class,
            ],
        ),
        new Get(
            uriTemplate: '/validator/unique/site_user_privileges',
            defaults: [
                'resource' => SiteUserPrivilege::class,
            ],
        ),
        new Get(
            uriTemplate: '/validator/unique/sites/code',
            defaults: [
                'resource' => ArchaeologicalSite::class,
            ],
        ),
        new Get(
            uriTemplate: '/validator/unique/sites/name',
            defaults: [
                'resource' => ArchaeologicalSite::class,
            ],
        ),
        new Get(
            uriTemplate: '/validator/unique/stratigraphic_unit_relationships',
            defaults: [
                'resource' => StratigraphicUnitRelationshipView::class,
            ],
        ),
        new Get(
            uriTemplate: '/validator/unique/stratigraphic_units',
            defaults: [
                'resource' => StratigraphicUnit::class,
            ],
        ),
        new Get(
            uriTemplate: '/validator/unique/vocabulary/botany/taxonomies/value',
            defaults: [
                'resource' => VocBotanyTaxonomy::class,
            ],
        ),
        new Get(
            uriTemplate: '/validator/unique/vocabulary/history/animals',
            defaults: [
                'resource' => VocHistoryAnimal::class,
            ],
        ),
        new Get(
            uriTemplate: '/validator/unique/vocabulary/history/locations',
            defaults: [
                'resource' => VocHistoryLocation::class,
            ],
        ),
        new Get(
            uriTemplate: '/validator/unique/vocabulary/history/plants',
            defaults: [
                'resource' => VocHistoryPlant::class,
            ],
        ),
        new Get(
            uriTemplate: '/validator/unique/vocabulary/zoo/taxonomies/code',
            defaults: [
                'resource' => VocZooTaxonomy::class,
            ],
        ),
        new Get(
            uriTemplate: '/validator/unique/vocabulary/zoo/taxonomies/value',
            defaults: [
                'resource' => VocZooTaxonomy::class,
            ],
        ),
    ],
    provider: ValidatorUniqueProvider::class,
)]
readonly class UniqueValidator
{
    public function __construct(private array $criteria, public int $valid)
    {
    }

    public function __get(string $name)
    {
        return match ($name) {
            'id' => implode('.', array_values($this->criteria)),
            default => array_key_exists($name, $this->criteria) ? $this->criteria[$name] : null,
        };
    }
}
