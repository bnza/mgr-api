<?php

declare(strict_types=1);

namespace App\Metadata;

use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\HttpOperation;
use ApiPlatform\OpenApi\Model;
use App\State\GeoserverExportProvider;

#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::IS_REPEATABLE)]
class ExportFeatureCollection extends HttpOperation implements CollectionOperationInterface
{
    /**
     * All GeoServer WFS outputFormats except GML2.
     * Keys = user-facing alias, values = GeoServer outputFormat string.
     */
    public const array OUTPUT_FORMATS = [
        'geojson' => 'application/json',
        'shapefile' => 'SHAPE-ZIP',
        'csv' => 'csv',
        'kml' => 'application/vnd.google-earth.kml+xml',
        'gml3' => 'gml3',
    ];

    public const string DEFAULT_FORMAT = 'geojson';

    public function __construct(
        string $uriTemplate,
        string $typeName,
    ) {
        parent::__construct(
            method: 'GET',
            uriTemplate: $uriTemplate,
            defaults: ['typeName' => $typeName],
            openapi: new Model\Operation(
                summary: 'Export filtered features via GeoServer',
                description: 'Exports features matching the same filters as GetFeatureCollection (without BBOX) in the requested format. Proxied from GeoServer. CRS is always EPSG:4326.',
                parameters: [
                    new Model\Parameter(
                        name: 'outputFormat',
                        in: 'query',
                        description: 'Export format: '.implode(', ', array_keys(self::OUTPUT_FORMATS)),
                        required: false,
                        schema: [
                            'type' => 'string',
                            'enum' => array_keys(self::OUTPUT_FORMATS),
                            'default' => self::DEFAULT_FORMAT,
                        ]
                    ),
                ]
            ),
            security: 'is_granted("IS_AUTHENTICATED_FULLY")',
            paginationEnabled: false,
            provider: GeoserverExportProvider::class,
        );
    }
}
