<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use App\Dto\Output\WfsGetFeatureCollectionExtentMatched;

class GeoserverAggregatedExtentMatchedProvider extends AbstractGeoserverFeatureCollectionProvider
{
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array|object|null
    {
        $defaults = $operation->getDefaults();
        $parentAccessor = $defaults['parentAccessor'] ?? null;

        [$typeName, $idField] = $this->getOperationDefaults($operation);

        $parentIdCounts = $this->getParentIdCounts($operation, $uriVariables, $context, $parentAccessor);

        $parentIds = array_keys($parentIdCounts);

        if ([] === $parentIdCounts) {
            return new WfsGetFeatureCollectionExtentMatched($typeName);
        }

        $wpsBody = $this->wpsBoundsBuilder->buildXmlBody(
            $typeName,
            $parentIds,
            'EPSG:3857',
            $idField,
        );

        $response = $this->httpClient->request('POST', self::WPS_URL, [
            'headers' => ['Content-Type' => 'application/xml'],
            'body' => $wpsBody,
        ]);

        return new WfsGetFeatureCollectionExtentMatched($typeName, $this->getResponseContent($response));
    }
}
