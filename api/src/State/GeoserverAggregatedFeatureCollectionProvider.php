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
        $entityTypeName = $defaults['entityTypeName'] ?? null;

        [$typeName, $idField, $geomField, $propertyNames] = $this->getOperationDefaults($operation);

        if ($propertyNames) {
            $propertyNames = array_unique(array_merge($propertyNames, [$geomField]));
        }

        $parentIdCounts = $this->getParentIdCounts($operation, $uriVariables, $context, $parentAccessor);

        if (!$wantsGeoJson) {
            return new JsonResponse($parentIdCounts, 200, ['Content-Type' => 'application/json']);
        }

        if ([] === $parentIdCounts) {
            return new Response(
                json_encode(['type' => 'FeatureCollection', 'features' => []], JSON_THROW_ON_ERROR),
                200,
                ['Content-Type' => 'application/geo+json; charset=utf-8']
            );
        }

        $parentIds = array_keys($parentIdCounts);
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

        // Inject number_matched into each Feature's properties and update FID prefix
        if (isset($geoJson['features'])) {
            $layerName = $entityTypeName ? (str_contains($entityTypeName, ':') ? explode(':', $entityTypeName)[1] : $entityTypeName) : null;
            foreach ($geoJson['features'] as &$feature) {
                $featureId = $feature['properties']['id'] ?? $feature['id'] ?? null;
                // If it's the FID, extract just the ID part after the dot
                if (is_string($featureId) && str_contains((string) $featureId, '.')) {
                    $featureId = explode('.', (string) $featureId)[1];
                }
                $feature['properties']['number_matched'] = $parentIdCounts[$featureId] ?? 0;

                if ($layerName && isset($feature['id']) && is_string($feature['id'])) {
                    $feature['id'] = preg_replace('/^[^.]+?\./', $layerName.':', (string) $feature['id']);
                }
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
