<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class GeoserverFeatureCollectionProvider extends AbstractGeoserverFeatureCollectionProvider
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
        /** @var \Symfony\Component\HttpFoundation\Request $request */
        $request = $context['request'];
        $requestedFormat = $request?->getRequestFormat();
        $bbox = $request?->get('bbox');
        $bbox = $bbox ? explode(',', $bbox) : [];

        if ($requestedFormat) {
            $wantsGeoJson = in_array(strtolower((string) $requestedFormat), ['geojson', 'application/geo+json'], true);
        } else {
            $accept = (string) ($context['request_headers']['accept'][0] ?? '');
            $wantsGeoJson = str_contains(strtolower($accept), 'application/geo+json');
        }

        [$typeName, $idField, $geomField, $propertyNames] = $this->getOperationDefaults($operation);

        if ($propertyNames) {
            $propertyNames = array_unique(array_merge($propertyNames, [$geomField]));
        }

        $ids = $this->getIds($operation, $uriVariables, $context);

        if (!$wantsGeoJson) {
            // Return the list of matching IDs as JSON, null is mapped to true meaning all the ids match
            return new JsonResponse($ids ?? true, 200, ['Content-Type' => 'application/json']);
        }

        if ([] === $ids) {
            return new Response(
                json_encode(['type' => 'FeatureCollection', 'features' => []], JSON_THROW_ON_ERROR),
                200,
                ['Content-Type' => 'application/geo+json; charset=utf-8']
            );
        }

        $filter = $this->xmlFilterBuilder->buildXmlBody(
            $typeName,
            $ids ?? [],
            $bbox,
            $idField,
            $geomField,
            'urn:ogc:def:crs:EPSG::3857',
            false,
            'application/json',
            'urn:ogc:def:crs:EPSG::3857',
            $propertyNames,
        );

        $params = $this->getDefaultWfsParams($typeName);
        $url = $this->getQueryUrl($params);

        $resp = $this->httpClient->request('POST', $url, [
            'headers' => ['Content-Type' => 'application/xml'],
            'body' => $filter,
            'timeout' => 120,
        ]);

        // Return raw GeoJSON (no json_decode) with the appropriate content type
        return new Response(
            $this->getResponseContent($resp),
            $resp->getStatusCode(),
            ['Content-Type' => 'application/geo+json; charset=utf-8']
        );
    }
}
