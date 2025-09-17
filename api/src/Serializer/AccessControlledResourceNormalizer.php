<?php

namespace App\Serializer;

use App\Entity\Auth\SiteUserPrivilege;
use App\Entity\Auth\User;
use App\Entity\Data\Analysis;
use App\Entity\Data\Context;
use App\Entity\Data\Join\ContextSample;
use App\Entity\Data\Join\ContextStratigraphicUnit;
use App\Entity\Data\Join\ContextZooAnalysis;
use App\Entity\Data\Join\MediaObject\BaseMediaObjectJoin;
use App\Entity\Data\Join\PotteryAnalysis;
use App\Entity\Data\Join\SampleStratigraphicUnit;
use App\Entity\Data\Join\ZooBoneAnalysis;
use App\Entity\Data\Join\ZooToothAnalysis;
use App\Entity\Data\Pottery;
use App\Entity\Data\Sample;
use App\Entity\Data\Site;
use App\Entity\Data\StratigraphicUnit;
use App\Entity\Data\Zoo\Bone;
use App\Entity\Data\Zoo\Tooth;
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
            BaseMediaObjectJoin::class => true,
            Bone::class => true,
            Context::class => true,
            ContextSample::class => true,
            ContextStratigraphicUnit::class => true,
            ContextZooAnalysis::class => true,
            Pottery::class => true,
            PotteryAnalysis::class => true,
            Sample::class => true,
            SampleStratigraphicUnit::class => true,
            Site::class => true,
            SiteUserPrivilege::class => true,
            StratigraphicUnit::class => true,
            Tooth::class => true,
            User::class => true,
            ZooBoneAnalysis::class => true,
            ZooToothAnalysis::class => true,
        ];
    }
}
