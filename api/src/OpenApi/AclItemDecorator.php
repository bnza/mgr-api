<?php

namespace App\OpenApi;

use ApiPlatform\OpenApi\Factory\OpenApiFactoryInterface;
use ApiPlatform\OpenApi\OpenApi;
use Symfony\Component\DependencyInjection\Attribute\AsDecorator;

#[AsDecorator('api_platform.openapi.factory', priority: 10)]
final readonly class AclItemDecorator implements OpenApiFactoryInterface
{
    public function __construct(
        private OpenApiFactoryInterface $decorated,
    ) {
    }

    public function __invoke(array $context = []): OpenApi
    {
        $openApi = ($this->decorated)($context);

        $components = $openApi->getComponents();
        $schemas = $components->getSchemas();

        foreach ($schemas as $key => $schema) {
            if (!$key || !str_contains($key, '.acl.read')) {
                continue;
            }

            $properties = $schema['properties'] ?? [];

            $properties['_acl'] = [
                'type' => 'object',
                'readOnly' => true,
                'description' => 'Access control metadata',
                'properties' => [
                    'canRead' => ['type' => 'boolean'],
                    'canUpdate' => ['type' => 'boolean'],
                    'canDelete' => ['type' => 'boolean'],
                ],
                'required' => ['canRead', 'canUpdate', 'canDelete'],
                'additionalProperties' => false,
            ];

            $schema['properties'] = $properties;
            $schemas[$key] = $schema;
        }

        return $openApi->withComponents($components->withSchemas($schemas));
    }
}
