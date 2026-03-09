<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use App\Dto\Output\WfsGetFeatureCollectionNumberMatched;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class GeoserverFeatureCollectionNumberMatchedProvider extends AbstractGeoserverFeatureCollectionProvider
{
    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     * @throws \JsonException
     * @throws \DOMException
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array|object|null
    {
        $ids = $this->getIds($operation, $uriVariables, $context);
        [$typeName, $idField, $geomField, $propertyNames] = $this->getOperationDefaults($operation);

        // Ensures the geom field is always returned
        if ($propertyNames) {
            $propertyNames = array_unique(array_merge($propertyNames, [$geomField]));
        }

        if ([] === $ids) {
            return new WfsGetFeatureCollectionNumberMatched($typeName);
        }
        $bbox = $this->getRequestBbox($context);
        $filter = $this->xmlFilterBuilder->buildXmlBody($typeName, $ids ?? [], $bbox, $idField, $geomField, $bbox[4] ?? null, false, 'application/json', null, $propertyNames);
        $params = $this->getDefaultWfsParams($typeName);
        $params['count'] = '0';
        $url = $this->getQueryUrl($params);
        $response = $this->httpClient->request('POST', $url, [
            'body' => $filter,
        ]);

        return new WfsGetFeatureCollectionNumberMatched($typeName, $this->getResponseContent($response));
    }
}
