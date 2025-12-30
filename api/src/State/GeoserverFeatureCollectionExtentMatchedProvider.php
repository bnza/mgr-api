<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use App\Dto\Output\WfsGetFeatureCollectionExtentMatched;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class GeoserverFeatureCollectionExtentMatchedProvider extends AbstractGeoserverFeatureCollectionProvider
{
    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array|object|null
    {
        $ids = $this->getIds($operation, $uriVariables, $context);
        [$typeName, $idField, $geomField] = $this->getOperationDefaults($operation);
        if ([] === $ids) {
            return new WfsGetFeatureCollectionExtentMatched($typeName);
        }

        $wpsBody = $this->wpsBoundsBuilder->buildExecuteBounds(
            $typeName,
            $ids ?? [],
            'EPSG:3857',
            $idField,
        );

        $response = $this->httpClient->request('POST', self::WPS_URL, [
            'headers' => ['Content-Type' => 'application/xml'],
            'body' => $wpsBody,
        ]);

        return new WfsGetFeatureCollectionExtentMatched($typeName, $this->getResponseContent($response));
    }

    protected function getSrs($context = []): ?string
    {
        /** @var \Symfony\Component\HttpFoundation\Request $request */
        $request = $context['request'];

        return $request?->get('srs', 'EPSG:3857');
    }
}
