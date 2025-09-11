<?php

namespace App\Entity\Vocabulary\MediaObject;

use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use App\Doctrine\Filter\SearchHierarchicalVocabularyFilter;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(
    name: 'media_object_types',
    schema: 'vocabulary'
)]
#[ORM\UniqueConstraint(columns: ['type_group', 'value'])]
#[ApiResource(
    shortName: 'VocMediaObjectType',
    operations: [
        new GetCollection(
            uriTemplate: '/media_object/types',
            order: ['group' => 'ASC', 'value' => 'ASC'],
        ),
        new Get(
            uriTemplate: '/media_object/types/{id}',
        ),
    ],
    routePrefix: 'vocabulary',
    paginationEnabled: false
)]
#[ApiFilter(
    SearchHierarchicalVocabularyFilter::class,
)]
class Type
{
    #[
        ORM\Id,
        ORM\GeneratedValue(strategy: 'SEQUENCE'),
        ORM\Column(type: 'smallint')
    ]
    public int $id;

    #[ORM\Column(name: 'type_group', type: 'string')]
    public string $group;

    #[ORM\Column(type: 'string')]
    public string $value;
}
