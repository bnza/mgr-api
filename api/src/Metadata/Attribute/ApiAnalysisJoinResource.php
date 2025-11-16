<?php

namespace App\Metadata\Attribute;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use App\Entity\Data\Analysis;

#[\Attribute(\Attribute::TARGET_CLASS)]
class ApiAnalysisJoinResource extends ApiResource
{
    public function __construct(
        string $subjectClass,
        string $templateParentResourceName,
        array $itemNormalizationGroups,
        string $templateParentCategoryName = '', // category name in plural form e.g. "contexts" or "sites"
    ) {
        // $templateParentCategoryName is used to create the URI template which pertains to a category (such as "context/zoo" or "site/anthropology")
        // if $templateParentCategoryName is not provided, $templateParentResourceName will be used directly, otherwise $templateParentCategoryName
        // will be prepended
        $templateParentResourcePath = implode('/', array_filter([$templateParentCategoryName, $templateParentResourceName]));

        // when $templateParentCategoryName is not provided, the URI template will be "/$templateParentResourceName/{parentId}/analyses"
        // otherwise, the URI template will be "/$templateParentCategoryName/{parentId}/analyses/$templateParentResourceName"
        $chunks = [
            (bool) $templateParentCategoryName ? $templateParentCategoryName : $templateParentResourceName, '{parentId}/analyses', $templateParentCategoryName ? $templateParentResourceName : null,
        ];
        $subjectTemplateParentResourcePath = '/'.implode('/', array_filter($chunks));

        parent::__construct(
            operations: [
                new Get(
                    uriTemplate: "/analyses/$templateParentResourcePath/{id}",
                ),
                new GetCollection(
                    uriTemplate: "/analyses/$templateParentResourcePath",
                ),
                new GetCollection(
                    uriTemplate: $subjectTemplateParentResourcePath,
                    uriVariables: [
                        'parentId' => new Link(
                            toProperty: 'subject',
                            fromClass: $subjectClass,
                        ),
                    ],
                    requirements: ['parentId' => '\d+'],
                ),
                new GetCollection(
                    uriTemplate: "/analyses/{parentId}/$templateParentResourcePath",
                    uriVariables: [
                        'parentId' => new Link(
                            toProperty: 'analysis',
                            fromClass: Analysis::class,
                        ),
                    ],
                    requirements: ['parentId' => '\d+'],
                ),
                new Post(
                    uriTemplate: "/analyses/$templateParentResourcePath",
                    denormalizationContext: [
                        'groups' => ['analysis_join:create'],
                    ],
                    securityPostDenormalize: "is_granted('create', object)",
                    validationContext: ['groups' => ['validation:analysis_join:create']]
                ),
                new Patch(
                    uriTemplate: "/analyses/$templateParentResourcePath/{id}",
                    denormalizationContext: [
                        'groups' => ['analysis_join:update'],
                    ],
                    security: "is_granted('update', object)",
                    validationContext: ['groups' => ['validation:analysis_join:update']]
                ),
                new Delete(
                    uriTemplate: "/analyses/$templateParentResourcePath/{id}",
                    security: "is_granted('delete', object)",
                    output: false
                ),
            ],
            routePrefix: 'data',
            normalizationContext: [
                'groups' => array_merge(['analysis_join:acl:read', 'analysis:acl:read'], $itemNormalizationGroups),
            ],
            order: ['id' => 'DESC'],
        );
    }
}
