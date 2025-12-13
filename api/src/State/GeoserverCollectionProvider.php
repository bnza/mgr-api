<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class GeoserverCollectionProvider implements ProviderInterface
{
    private const string BASE_URL = 'http://geoserver:8080/geoserver/wfs';

    public function __construct(
        #[Autowire(service: 'api_platform.doctrine.orm.state.collection_provider')]
        private readonly ProviderInterface $doctrineOrmCollectionProvider,
        #[Autowire(service: 'monolog.http_client')]
        private readonly HttpClientInterface $httpClient,
    ) {
    }

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

        // Determine requested format (Accept header or ?_format/format)
        //        $requestedFormat = $format
        //            ?? $context['request_format']
        //            ?? $context['filters']['_format']
        //            ?? $context['filters']['format']
        //            ?? null;

        if ($requestedFormat) {
            $wantsGeoJson = in_array(strtolower((string) $requestedFormat), ['geojson', 'application/geo+json'], true);
        } else {
            $accept = (string) ($context['request_headers']['accept'][0] ?? '');
            $wantsGeoJson = str_contains(strtolower($accept), 'application/geo+json');
        }

        if (!$wantsGeoJson) {
            // Fall back to the normal collection provider (ORM) for jsonld/json
            return $this->doctrineOrmCollectionProvider->provide($operation, $uriVariables, $context);
        }

        $defaults = $operation->getDefaults();
        if (!is_array($defaults) && !array_key_exists('typeName', $defaults)) {
            throw new HttpException(500, 'Invalid operation defaults, missing typeName');
        }
        $typeName = $defaults['typeName'];
        $geomField = $defaults['geomField'] ?? 'the_geom';

        $collection = $this->doctrineOrmCollectionProvider->provide($operation, $uriVariables, $context);

        $ids = [];
        foreach ($collection as $item) {
            if (is_object($item) && method_exists($item, 'getId')) {
                $ids[] = $item->getId();
            }
        }

        if ([] === $ids) {
            return new Response(
                json_encode(['type' => 'FeatureCollection', 'features' => []], JSON_THROW_ON_ERROR),
                200,
                ['Content-Type' => 'application/geo+json; charset=utf-8']
            );
        }

        $cql = sprintf('id IN (%s)', implode(',', $ids));
        if ($bbox) {
            $cql .= sprintf(" AND BBOX(%s,%s,%s,%s,%s,'%s')", $geomField, $bbox[0], $bbox[1], $bbox[2], $bbox[3], $bbox[4] ?? 'EPSG:3857');
        }

        $wfsParams = [
            'service' => 'WFS',
            'version' => '2.0.0',
            'request' => 'GetFeature',
            'typeNames' => $typeName,
            'outputFormat' => 'application/json',
            'CQL_FILTER' => $cql,
            'exceptions' => 'application/json',
            'srsName' => 'urn:ogc:def:crs:EPSG::3857',
        ];

        $url = self::BASE_URL.'?'.http_build_query($wfsParams, '', '&', PHP_QUERY_RFC3986);

        $resp = $this->httpClient->request('GET', $url, [
            'headers' => ['Accept' => 'application/json'],
            'timeout' => 20,
        ]);

        if ($resp->getStatusCode() < 400) {
            // Return raw GeoJSON (no json_decode) with the appropriate content type
            return new Response(
                $resp->getContent(false),
                $resp->getStatusCode(),
                ['Content-Type' => 'application/geo+json; charset=utf-8']
            );
        }

        $content = json_decode($resp->getContent(false), true);
        throw new HttpException($resp->getStatusCode(), $content['exceptions'][0]['text'] ?? 'Geoserver error');
    }
}
