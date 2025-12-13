<?php

namespace App\Encoder;

use Symfony\Component\Serializer\Encoder\JsonEncoder;

class GeoJsonEncoder extends JsonEncoder
{
    public function supportsEncoding(string $format, array $context = []): bool
    {
        return 'geojson' === $format;
    }

    public function supportsDecoding(string $format, array $context = []): bool
    {
        return 'geojson' === $format;
    }
}
