<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class GeoserverAggregatedFeatureCollectionProvider extends AbstractGeoserverFeatureCollectionProvider
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

        if ($requestedFormat) {
            $wantsGeoJson = in_array(strtolower((string) $requestedFormat), ['geojson', 'application/geo+json'], true);
        } else {
            $accept = (string) ($context['request_headers']['accept'][0] ?? '');
            $wantsGeoJson = str_contains(strtolower($accept), 'application/geo+json');
        }

        $defaults = $operation->getDefaults();
        $parentAccessor = $defaults['parentAccessor'] ?? null;

        [$typeName, $idField, $geomField, $propertyNames] = $this->getOperationDefaults($operation);

        if ($propertyNames) {
            $propertyNames = array_unique(array_merge($propertyNames, [$geomField]));
        }

        $parentIdCounts = $this->getParentIdCounts($operation, $uriVariables, $context, $parentAccessor);

        if (!$wantsGeoJson) {
            return new JsonResponse($parentIdCounts ?? true, 200, ['Content-Type' => 'application/json']);
        }

        if ([] === $parentIdCounts) {
            return new Response(
                json_encode(['type' => 'FeatureCollection', 'features' => []], JSON_THROW_ON_ERROR),
                200,
                ['Content-Type' => 'application/geo+json; charset=utf-8']
            );
        }

        $parentIds = null !== $parentIdCounts ? array_keys($parentIdCounts) : [];
        $bbox = $request?->get('bbox');
        $bbox = $bbox ? explode(',', $bbox) : [];

        $filter = $this->xmlFilterBuilder->buildXmlBody(
            $typeName,
            $parentIds,
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

        $geoJson = json_decode($this->getResponseContent($resp), true, 512, JSON_THROW_ON_ERROR);

        // Inject number_matched into each Feature's properties
        if (isset($geoJson['features'])) {
            foreach ($geoJson['features'] as &$feature) {
                $featureId = $feature['properties']['id'] ?? $feature['id'] ?? null;
                $feature['properties']['number_matched'] = $parentIdCounts[$featureId] ?? (null === $parentIdCounts ? 1 : 0);
            }
            unset($feature);
        }

        return new Response(
            json_encode($geoJson, JSON_THROW_ON_ERROR),
            $resp->getStatusCode(),
            ['Content-Type' => 'application/geo+json; charset=utf-8']
        );
    }
}
