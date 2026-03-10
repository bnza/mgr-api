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

        // null means all entities match → query GeoServer for the total count of the parent featureType
        if (null === $parentIdCounts) {
            $params = $this->getDefaultWfsParams($typeName);
            $params['count'] = '0';
            $url = $this->getQueryUrl($params);
            $response = $this->httpClient->request('POST', $url, [
                'body' => $this->xmlFilterBuilder->buildXmlBody($typeName, [], [], 'id', 'the_geom'),
            ]);

            return new WfsGetFeatureCollectionNumberMatched($typeName, $this->getResponseContent($response));
        }

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
