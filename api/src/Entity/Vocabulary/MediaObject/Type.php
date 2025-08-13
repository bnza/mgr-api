<?php

namespace App\Entity\Vocabulary\MediaObject;

use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity]
#[ORM\Table(
    name: 'media_object_types',
    schema: 'vocabulary'
)]
#[ORM\UniqueConstraint(columns: ['type_group', 'value'])]
#[ApiResource(
    shortName: 'MediaObjectType',
    operations: [
        new GetCollection(
            uriTemplate: '/media_object/types',
            order: ['group' => 'ASC', 'value' => 'ASC'],
        ),
    ],
    routePrefix: 'vocabulary',
    paginationEnabled: false
)]
#[ApiFilter(
    SearchFilter::class,
    properties: [
        'value' => 'ipartial',
    ]
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
    #[Groups([
        'media_object:acl:read',
    ])]
    public string $group;

    #[ORM\Column(type: 'string')]
    #[Groups([
        'media_object:acl:read',
    ])]
    public string $value;
}
