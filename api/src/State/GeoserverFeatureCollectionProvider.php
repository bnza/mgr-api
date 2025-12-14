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

        [$typeName, $idField, $geomField] = $this->getOperationDefaults($operation);

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

        $filters = [];

        if ($ids) {
            $filters[] = sprintf('%s IN (%s)', $idField, implode(',', $ids));
        }

        if ($bbox) {
            $filters[] = sprintf("BBOX(%s,%s,%s,%s,%s,'%s')", $geomField, $bbox[0], $bbox[1], $bbox[2], $bbox[3], $bbox[4] ?? 'EPSG:3857');
        }

        $wfsParams = $this->getDefaultWfsParams($typeName);

        $wfsParams['srsName'] = 'urn:ogc:def:crs:EPSG::3857';

        if ($filters) {
            $wfsParams['CQL_FILTER'] = implode(' AND ', $filters);
        }

        $url = self::BASE_URL.'?'.http_build_query($wfsParams, '', '&', PHP_QUERY_RFC3986);

        $resp = $this->httpClient->request('GET', $url, [
            'headers' => ['Accept' => 'application/json'],
            'timeout' => 20,
        ]);

        // Return raw GeoJSON (no json_decode) with the appropriate content type
        return new Response(
            $this->getResponseContent($resp),
            $resp->getStatusCode(),
            ['Content-Type' => 'application/geo+json; charset=utf-8']
        );
    }
}
