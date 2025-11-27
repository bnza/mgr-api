<?php

declare(strict_types=1);

namespace App\Serializer;

use App\Entity\Data\MediaObject;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

/**
 * Decorates the ObjectNormalizer to coerce MediaObject.public to bool for multipart inputs.
 */
final readonly class MultipartBooleanPublicFieldDenormalizer implements DenormalizerInterface
{
    public function __construct(
        #[Autowire(service: 'serializer.normalizer.object')]
        private DenormalizerInterface $inner,
    ) {
    }

    public function supportsDenormalization($data, string $type, ?string $format = null, array $context = []): bool
    {
        if ('multipart' !== $format) {
            return false;
        }

        if (MediaObject::class === $type) {
            return true;
        }

        return $this->inner->supportsDenormalization($data, $type, $format);
    }

    /**
     * @param array<string,mixed>|mixed $data
     *
     * @throws ExceptionInterface
     */
    public function denormalize($data, string $type, ?string $format = null, array $context = []): mixed
    {
        if (MediaObject::class === $type && \is_array($data) && array_key_exists('public', $data)) {
            $data['public'] = $this->coerceToBoolIfString($data['public']);
        }

        // Hand off to the real ObjectNormalizer (the decorated inner normalizer)
        return $this->inner->denormalize($data, $type, $format, $context);
    }

    /**
     * Accepts string/number/bool and returns a strict bool when possible; otherwise returns original value.
     */
    private function coerceToBoolIfString(mixed $value): mixed
    {
        if (\is_bool($value)) {
            return $value; // already boolean
        }

        if (!\is_string($value) && !\is_int($value) && !\is_float($value)) {
            return $value; // leave as-is, let validation handle it
        }

        $raw = strtolower(trim((string) $value));

        // Recognizes: "1", "true", "on", "yes" => true; "0", "false", "off", "no" => false
        $bool = filter_var($raw, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE);

        return $bool ?? $value; // if null (unrecognized), leave original to let validation handle it
    }

    public function getSupportedTypes(?string $format): array
    {
        return [MediaObject::class => true];
    }
}
