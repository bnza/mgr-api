<?php

namespace App\Serializer;

use ApiPlatform\Metadata\CollectionOperationInterface;
use App\Service\AclDataMerger;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

final class AccessControlledResourceCollectionNormalizer implements NormalizerInterface
{
    private const ALREADY_CALLED = 'ACCESS_CONTROLLED_COLLECTION_NORMALIZER_ALREADY_CALLED';

    public function __construct(
        #[Autowire(service: 'api_platform.hydra.normalizer.collection')]
        private readonly NormalizerInterface $inner,
        private readonly AclDataMerger $aclDataMerger,
    ) {
    }

    public function supportsNormalization($data, ?string $format = null, array $context = []): bool
    {
        if (isset($context[self::ALREADY_CALLED])) {
            return false;
        }
        // Only for Hydra JSON-LD collections
        if ('jsonld' !== $format) {
            return false;
        }

        if (!$this->inner->supportsNormalization($data, $format, $context)) {
            return false;
        }

        // Ensure we are normalizing a collection operation of an API resource
        if (!isset($context['resource_class'])) {
            return false;
        }

        $isCollection = (
            (isset($context['operation']) && $context['operation'] instanceof CollectionOperationInterface)
            || isset($context['collection_operation_name'])
        );

        return $isCollection;
    }

    public function normalize($data, ?string $format = null, array $context = []): array|string|int|float|bool|\ArrayObject|null
    {
        $context[self::ALREADY_CALLED] = true;

        // Let Hydra build the root structure first
        $normalizedData = $this->inner->normalize($data, $format, $context);
        if (!\is_array($normalizedData)) {
            return $normalizedData; // safety
        }

        // Merge only when we know the resource class (collection of API resources)
        if (!isset($context['resource_class'])) {
            return $normalizedData;
        }

        return $this->aclDataMerger->mergeCollection($normalizedData, $context);
    }

    public function getSupportedTypes(?string $format): array
    {
        return $this->inner->getSupportedTypes($format);
    }
}
