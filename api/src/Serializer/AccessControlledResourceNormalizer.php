<?php

namespace App\Serializer;

use App\Entity\Auth\SiteUserPrivilege;
use App\Entity\Auth\User;
use App\Entity\Data\Analysis;
use App\Entity\Data\Botany\Charcoal;
use App\Entity\Data\Botany\Seed;
use App\Entity\Data\Context;
use App\Entity\Data\History\Animal;
use App\Entity\Data\History\Plant;
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
use App\Entity\Data\Join\MediaObject\BaseMediaObjectJoin;
use App\Entity\Data\Join\SampleStratigraphicUnit;
use App\Entity\Data\Join\SedimentCoreDepth;
use App\Entity\Data\MicrostratigraphicUnit;
use App\Entity\Data\Pottery;
use App\Entity\Data\Sample;
use App\Entity\Data\SedimentCore;
use App\Entity\Data\Site;
use App\Entity\Data\StratigraphicUnit;
use App\Entity\Data\Zoo\Bone;
use App\Entity\Data\Zoo\Tooth;
use App\Entity\Vocabulary\Botany\Taxonomy as VocBotanyTaxonomy;
use App\Entity\Vocabulary\History\Animal as VocHistoryAnimal;
use App\Entity\Vocabulary\History\Location;
use App\Entity\Vocabulary\History\Plant as VocHistoryPlant;
use App\Entity\Vocabulary\Zoo\Taxonomy as VocZooTaxonomy;
use App\Service\AclDataMerger;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareTrait;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

final class AccessControlledResourceNormalizer implements NormalizerInterface, NormalizerAwareInterface
{
    use NormalizerAwareTrait;

    private const ALREADY_CALLED = 'ACCESS_CONTROLLED_ATTRIBUTE_NORMALIZER_ALREADY_CALLED';

    public function __construct(
        #[Autowire(service: 'api_platform.jsonld.normalizer.item')]
        private readonly NormalizerInterface $decorated,
        private readonly AclDataMerger       $aclDataMerger,
    )
    {
    }

    public function normalize(
        mixed   $data,
        ?string $format = null,
        array   $context = [],
    ): float|int|bool|\ArrayObject|array|string|null
    {
        $context[self::ALREADY_CALLED] = true;
        $normalizedData = $this->decorated->normalize($data, $format, $context);

        return $this->aclDataMerger->merge($normalizedData, $data);
    }

    public function supportsNormalization($data, $format = null, array $context = []): bool
    {
        if (isset($context[self::ALREADY_CALLED])) {
            return false;
        }

        // Normalize only the requested resource
        if (array_key_exists('object', $context) && $context['resource_class'] !== get_class($context['object'])) {
            return false;
        }

        if (!$this->decorated->supportsNormalization($data, $format, $context)) {
            return false;
        }

        return $this->aclDataMerger->hasAclContext($context);
    }

    public function getSupportedTypes(?string $format): array
    {
        return [
            Analysis::class => true,
            AnalysisBotanyCharcoal::class => true,
            AnalysisBotanySeed::class => true,
            AnalysisContextBotany::class => true,
            AnalysisContextZoo::class => true,
            AnalysisIndividual::class => true,
            AnalysisSampleMicrostratigraphy::class => true,
            AnalysisSiteAnthropology::class => true,
            AnalysisPottery::class => true,
            AnalysisZooBone::class => true,
            AnalysisZooTooth::class => true,
            Animal::class => true,
            BaseMediaObjectJoin::class => true,
            Bone::class => true,
            Charcoal::class => true,
            Context::class => true,
            ContextStratigraphicUnit::class => true,
            VocHistoryAnimal::class => true,
            VocHistoryPlant::class => true,
            Individual::class => true,
            Location::class => true,
            MicrostratigraphicUnit::class => true,
            Pottery::class => true,
            Plant::class => true,
            Sample::class => true,
            SampleStratigraphicUnit::class => true,
            SedimentCore::class => true,
            SedimentCoreDepth::class => true,
            Seed::class => true,
            Site::class => true,
            SiteUserPrivilege::class => true,
            StratigraphicUnit::class => true,
            Tooth::class => true,
            User::class => true,
            VocBotanyTaxonomy::class => true,
            VocZooTaxonomy::class => true,
        ];
    }
}
