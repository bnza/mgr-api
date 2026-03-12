<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use App\Dto\Output\WfsGetFeatureCollectionNumberMatched;

class GeoserverAggregatedNumberMatchedProvider extends AbstractGeoserverFeatureCollectionProvider
{
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array|object|null
    {
        $defaults = $operation->getDefaults();
        $parentAccessor = $defaults['parentAccessor'] ?? null;

        [$typeName] = $this->getOperationDefaults($operation);

        $parentIdCounts = $this->getParentIdCounts($operation, $uriVariables, $context, $parentAccessor);

        // The number of distinct parent IDs is the number of matched spatial features
        $numberMatched = count($parentIdCounts);

        return new WfsGetFeatureCollectionNumberMatched(
            $typeName,
            json_encode([
                'numberMatched' => $numberMatched,
                'timeStamp' => (new \DateTimeImmutable())->format(DATE_ATOM),
            ], JSON_THROW_ON_ERROR)
        );
    }
}
