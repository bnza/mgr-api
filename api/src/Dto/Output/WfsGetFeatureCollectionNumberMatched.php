<?php

namespace App\Dto\Output;

use function Symfony\Component\Clock\now;

readonly class WfsGetFeatureCollectionNumberMatched
{
    public int $numberMatched;
    public string $timeStamp;

    public function __construct(public string $typeName, ?string $jsonResponse = null)
    {
        $response = $jsonResponse ? json_decode($jsonResponse, true, 512, JSON_THROW_ON_ERROR) : [];
        $this->numberMatched = $response['numberMatched'] ?? 0;
        $this->timeStamp = $response['timeStamp'] ?? now()->format(DATE_ATOM);
    }

    public function getId()
    {
        return sprintf('%s:%s', $this->typeName, $this->timeStamp);
    }
}
