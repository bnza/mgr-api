<?php

namespace App\Entity\Vocabulary\Pottery;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(
    name: 'pottery_functional_groups',
    schema: 'vocabulary',
)]
#[ApiResource(
    shortName: 'FunctionalGroup',
    operations: [
        new GetCollection(
            uriTemplate: '/pottery/functional_groups',
            order: ['value' => 'ASC'],
        ),
        new Get(
            uriTemplate: '/pottery/functional_groups/{id}',
        ),
    ],
    routePrefix: 'vocabulary',
    paginationEnabled: false
)]
class FunctionalGroup
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
