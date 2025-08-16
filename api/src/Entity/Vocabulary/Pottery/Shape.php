<?php

namespace App\Entity\Vocabulary\Pottery;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(
    name: 'pottery_shapes',
    schema: 'vocabulary',
)]
#[ApiResource(
    shortName: 'Shape',
    operations: [
        new GetCollection(
            uriTemplate: '/pottery/shapes',
            order: ['value' => 'ASC'],
        ),
        new Get(
            uriTemplate: '/pottery/shapes/{id}',
        ),
    ],
    routePrefix: 'vocabulary',
    paginationEnabled: false
)]
class Shape
{
    #[
        ORM\Id,
        ORM\GeneratedValue(strategy: 'SEQUENCE'),
        ORM\Column(type: 'smallint')
    ]
    public int $id;

    #[ORM\Column(type: 'string', unique: true)]
    public string $value;
}
