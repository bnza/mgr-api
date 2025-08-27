<?php

namespace App\Metadata\Attribute;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Post;

#[\Attribute(\Attribute::TARGET_CLASS)]
class ApiMediaObjectJoinResource extends ApiResource
{
    public function __construct(
        string $itemClass,
        string $templateParentResourceName,
        array $itemNormalizationGroups,
    ) {
        parent::__construct(
            operations: [
                new Get(),
                new GetCollection(),
                new GetCollection(
                    uriTemplate: "/$templateParentResourceName/{parentId}/media_objects",
                    uriVariables: [
                        'parentId' => new Link(
                            toProperty: 'item',
                            fromClass: $itemClass,
                        ),
                    ],
                ),
                new Post(
                    denormalizationContext: [
                        'groups' => ['media_object_join:create'],
                    ],
                    securityPostDenormalize: "is_granted('create', object)"
                ),
                new Delete(
                    security: "is_granted('delete', object)",
                    output: false
                ),
            ],
            routePrefix: 'data',
            normalizationContext: [
                'groups' => array_merge(['media_object_join:acl:read', 'media_object:acl:read'], $itemNormalizationGroups),
            ]
        );
    }
}
