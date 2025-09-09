<?php

namespace App\Serializer;

use App\Entity\Data\MediaObject;
use App\Service\AclDataMerger;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Vich\UploaderBundle\Storage\StorageInterface;

final class MediaObjectNormalizer implements NormalizerInterface
{
    private const string ALREADY_CALLED = 'MEDIA_OBJECT_NORMALIZER_ALREADY_CALLED';

    public function __construct(
        #[Autowire(service: 'api_platform.jsonld.normalizer.item')]
        private readonly NormalizerInterface $normalizer,
        private readonly StorageInterface $storage,
        private readonly AclDataMerger $aclDataMerger,
    ) {
    }

    public function normalize(
        $data,
        ?string $format = null,
        array $context = [],
    ): array|string|int|float|bool|\ArrayObject|null {
        $context[self::ALREADY_CALLED] = true;

        /* @var MediaObject $data */
        $data->setContentUrl($this->storage->resolveUri($data, 'file'));

        $normalizedData = $this->normalizer->normalize($data, $format, $context);
        if ($this->aclDataMerger->hasAclContext($context)) {
            return $this->aclDataMerger->merge($normalizedData, $data);
        }

        return $normalizedData;
    }

    public function supportsNormalization($data, ?string $format = null, array $context = []): bool
    {
        if (isset($context[self::ALREADY_CALLED])) {
            return false;
        }

        return is_object($data) && in_array(get_class($data), [MediaObject::class]);
    }

    public function getSupportedTypes(?string $format): array
    {
        return [
            'object' => null,
            '*' => false,
            MediaObject::class => true,
        ];
    }
}
