<?php

declare(strict_types=1);

namespace App\State;

use ApiPlatform\Metadata\Operation;
use App\Metadata\ExportFeatureCollection;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class GeoserverExportProvider extends AbstractGeoserverFeatureCollectionProvider
{
    private const array CONTENT_TYPE_MAP = [
        'geojson' => 'application/geo+json',
        'shapefile' => 'application/zip',
        'csv' => 'text/csv; charset=UTF-8',
        'kml' => 'application/vnd.google-earth.kml+xml',
        'gml3' => 'application/xml',
    ];

    private const array FILE_EXTENSION_MAP = [
        'geojson' => 'geojson',
        'shapefile' => 'zip',
        'csv' => 'csv',
        'kml' => 'kml',
        'gml3' => 'gml',
    ];

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        $request = $context['request'];

        // Resolve output format
        $formatAlias = $request->get('outputFormat', ExportFeatureCollection::DEFAULT_FORMAT);
        if (!array_key_exists($formatAlias, ExportFeatureCollection::OUTPUT_FORMATS)) {
            throw new BadRequestHttpException(sprintf('Invalid outputFormat "%s". Allowed: %s', $formatAlias, implode(', ', array_keys(ExportFeatureCollection::OUTPUT_FORMATS))));
        }
        $geoserverOutputFormat = ExportFeatureCollection::OUTPUT_FORMATS[$formatAlias];

        // Get filtered IDs (reuses Doctrine ORM filters, same as GetFeatureCollection)
        [$typeName, $idField, $geomField] = $this->getOperationDefaults($operation);
        $ids = $this->getIds($operation, $uriVariables, $context);

        // Empty result set
        if ([] === $ids) {
            return new Response('', 204);
        }

        // Build XML filter body (NO BBOX, always EPSG:4326)
        $filter = $this->xmlFilterBuilder->buildXmlBody(
            $typeName,
            $ids ?? [],
            null,
            $idField,
            $geomField,
            null,
            false,
            $geoserverOutputFormat,
            'EPSG:4326',
        );

        $params = $this->getDefaultWfsParams($typeName);
        $url = $this->getQueryUrl($params);

        $resp = $this->httpClient->request('POST', $url, [
            'headers' => ['Content-Type' => 'application/xml'],
            'body' => $filter,
            'timeout' => 120,
        ]);

        // Stream GeoServer response to client
        $contentType = self::CONTENT_TYPE_MAP[$formatAlias] ?? 'application/octet-stream';
        $ext = self::FILE_EXTENSION_MAP[$formatAlias] ?? 'bin';
        $baseName = str_contains($typeName, ':') ? substr($typeName, strpos($typeName, ':') + 1) : $typeName;

        return new Response(
            $resp->getContent(),
            $resp->getStatusCode(),
            [
                'Content-Type' => $contentType,
                'Content-Disposition' => sprintf('attachment; filename="%s.%s"', $baseName, $ext),
            ]
        );
    }
}
