<?php

declare(strict_types=1);

namespace App\Metadata;

use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\HttpOperation;
use ApiPlatform\OpenApi\Model;
use App\State\GeoserverAggregatedFeatureCollectionProvider;

#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::IS_REPEATABLE)]
class GetAggregatedFeatureCollection extends HttpOperation implements CollectionOperationInterface
{
    public function __construct(
        string $uriTemplate,
        string $typeName,
        string $parentAccessor,
        array $normalizationContext = ['groups' => ['feature_collection:json:read']],
        array $propertyNames = [],
    ) {
        parent::__construct(
            method: 'GET',
            uriTemplate: $uriTemplate,
            formats: ['geojson' => 'application/geo+json', 'json' => 'application/json'],
            defaults: ['typeName' => $typeName, 'parentAccessor' => $parentAccessor, 'propertyNames' => $propertyNames],
            openapi: new Model\Operation(
                responses: [
                    '200' => new Model\Response(
                        description: 'GeoJSON FeatureCollection aggregated by spatial parent, depending on the requested format return a geojson FeatureCollection with number_matched property or a {parentId: count} map.',
                        content: new \ArrayObject(
                            [
                                'application/geo+json' => new Model\MediaType(
                                    schema: new \ArrayObject([
                                        '$ref' => '#/components/schemas/GeoJSONFeatureCollection',
                                    ])
                                ),
                                'application/json' => new Model\MediaType(
                                    schema: new \ArrayObject([
                                        '$ref' => '#/components/schemas/MatchingFeaturesParentIdCounts',
                                    ]),
                                    examples: new \ArrayObject([
                                        'parentIdCounts' => [
                                            'summary' => 'Map of parent IDs to matched entity counts',
                                            'value' => ['1' => 5, '2' => 3, '3' => 12],
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
                summary: 'Aggregated GeoServer FeatureCollection (GeoJSON)',
                description: 'Returns a GeoJSON FeatureCollection from GeoServer aggregated by spatial parent (site/location), with number_matched property on each feature.',
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
            provider: GeoserverAggregatedFeatureCollectionProvider::class
        );
    }
}
