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
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array|object|null
    {
        $ids = $this->getIds($operation, $uriVariables, $context);
        [$typeName, $idField, $geomField] = $this->getOperationDefaults($operation);
        if ([] === $ids) {
            return new WfsGetFeatureCollectionNumberMatched($typeName);
        }
        $bbox = $this->getRequestBbox($context);
        $filter = $this->xmlFilterBuilder->buildGetFeature($typeName, $ids ?? [], $bbox, $idField, $geomField, $bbox[4] ?? null, true);
        $params = $this->getDefaultWfsParams($typeName);
        $params['count'] = '0';
        $url = $this->getQueryUrl($params);
        $response = $this->httpClient->request('POST', $url, [
            'body' => $filter,
        ]);

        return new WfsGetFeatureCollectionNumberMatched($typeName, $this->getResponseContent($response));
    }
}
