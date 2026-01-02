<?php

declare(strict_types=1);

namespace App\Metadata;

use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\HttpOperation;
use ApiPlatform\OpenApi\Model;
use App\State\GeoserverFeatureCollectionProvider;

#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::IS_REPEATABLE)]
class GetFeatureCollection extends HttpOperation implements CollectionOperationInterface
{
    public function __construct(
        string $uriTemplate,
        string $typeName,
        array $normalizationContext = ['groups' => ['feature_collection:json:read']],
    ) {
        parent::__construct(
            method: 'GET',
            uriTemplate: $uriTemplate,
            formats: ['geojson' => 'application/geo+json', 'json' => 'application/json'],
            defaults: ['typeName' => $typeName],
            openapi: new Model\Operation(
                responses: [
                    '200' => new Model\Response(
                        description: 'GeoJSON FeatureCollection, depending on the requested format return a geojson FeatureCollection or an array of IDs.',
                        content: new \ArrayObject(
                            [
                                'application/geo+json' => new Model\MediaType(
                                    schema: new \ArrayObject([
                                        '$ref' => '#/components/schemas/GeoJSONFeatureCollection',
                                    ])
                                ),
                                'application/json' => new Model\MediaType(
                                    schema: new \ArrayObject([
                                        '$ref' => '#/components/schemas/MatchingFeaturesIds',
                                    ]),
                                    examples: new \ArrayObject([
                                        'numbers' => [
                                            'summary' => 'Array of IDs example',
                                            'value' => [7, 8, 9],
                                        ],
                                        'allMatched' => [
                                            'summary' => 'All matched example',
                                            'value' => true,
                                        ],
                                    ])
                                ),
                            ]
                        )
                    ),
                ],
                summary: 'GeoServer FeatureCollection (GeoJSON)',
                description: 'Returns a GeoJSON FeatureCollection streamed from GeoServer.',
                parameters: [
                    new Model\Parameter(
                        name: 'bbox', in: 'query', description: 'BBOX filter: minx,miny,maxx,maxy[,CRS]. CRS defaults to EPSG:3857.',
                        required: false,
                        schema: ['type' => 'string']
                    ),
                ]
            ),
            paginationEnabled: false,
            normalizationContext: $normalizationContext,
            provider: GeoserverFeatureCollectionProvider::class
        );
    }
}
