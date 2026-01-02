<?php

namespace App\Dto\Output;

use Symfony\Component\Serializer\Annotation\Groups;

use function Symfony\Component\Clock\now;

readonly class WfsGetFeatureCollectionNumberMatched
{
    #[Groups(['wfs_number_matched:read'])]
    public int $numberMatched;

    #[Groups(['wfs_number_matched:read'])]
    public string $timeStamp;

    public function __construct(
        #[Groups(['wfs_number_matched:read'])]
        public string $typeName,
        ?string $jsonResponse = null,
    ) {
        $response = $jsonResponse ? json_decode($jsonResponse, true, 512, JSON_THROW_ON_ERROR) : [];
        $this->numberMatched = $response['numberMatched'] ?? 0;
        $this->timeStamp = $response['timeStamp'] ?? now()->format(DATE_ATOM);
    }

    #[Groups(['wfs_number_matched:read'])]
    public function getId()
    {
        return sprintf('%s:%s', $this->typeName, $this->timeStamp);
    }
}
