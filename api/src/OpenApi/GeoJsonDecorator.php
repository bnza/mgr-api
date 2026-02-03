<?php

namespace App\OpenApi;

use ApiPlatform\OpenApi\Factory\OpenApiFactoryInterface;
use ApiPlatform\OpenApi\OpenApi;
use Symfony\Component\DependencyInjection\Attribute\AsDecorator;

#[AsDecorator('api_platform.openapi.factory', priority: -10)]
final readonly class GeoJsonDecorator implements OpenApiFactoryInterface
{
    public function __construct(
        private OpenApiFactoryInterface $decorated,
    ) {
    }

    public function __invoke(array $context = []): OpenApi
    {
        $openApi = ($this->decorated)($context);

        $schemas = $openApi->getComponents()->getSchemas();

        // Geometry (Point-only for your case; expand as needed)
        $schemas['GeoJSONPoint'] = [
            'type' => 'object',
            'required' => ['type', 'coordinates'],
            'properties' => [
                'type' => ['type' => 'string', 'enum' => ['Point']],
                'coordinates' => [
                    'type' => 'array',
                    'items' => ['type' => 'number'],
                    'minItems' => 2,
                ],
            ],
        ];

        $schemas['GeoJSONFeature'] = [
            'type' => 'object',
            'required' => ['type', 'geometry', 'properties'],
            'properties' => [
                'type' => ['type' => 'string', 'enum' => ['Feature']],
                'id' => ['type' => 'string'],
                'geometry' => ['$ref' => '#/components/schemas/GeoJSONPoint'],
                'geometry_name' => ['type' => 'string'],
                'properties' => [
                    'type' => 'object',
                    'additionalProperties' => true,
                ],
            ],
        ];

        $schemas['GeoJSONFeatureCollection'] = [
            'type' => 'object',
            'required' => ['type', 'features'],
            'properties' => [
                'type' => ['type' => 'string', 'enum' => ['FeatureCollection']],
                'features' => [
                    'type' => 'array',
                    'items' => ['$ref' => '#/components/schemas/GeoJSONFeature'],
                ],
                'totalFeatures' => ['type' => 'integer'],
                'numberMatched' => ['type' => 'integer'],
                'numberReturned' => ['type' => 'integer'],
                'timeStamp' => ['type' => 'string', 'format' => 'date-time'],
                'crs' => [
                    'type' => 'object',
                    'properties' => [
                        'type' => ['type' => 'string'],
                        'properties' => [
                            'type' => 'object',
                            'properties' => [
                                'name' => ['type' => 'string'],
                            ],
                        ],
                    ],
                ],
            ],
            'additionalProperties' => true,
        ];

        $schemas['MatchingFeaturesIds'] = [
            'oneOf' => [
                [ // number[]
                    'type' => 'array',
                    'items' => ['type' => 'number'],
                ],
                [ // the literal boolean true (use enum to be OpenAPI 3.0-safe)
                    'type' => 'boolean',
                    'enum' => [true],
                ],
            ],
        ];

        return $openApi->withComponents($openApi->getComponents()->withSchemas($schemas));
    }
}
