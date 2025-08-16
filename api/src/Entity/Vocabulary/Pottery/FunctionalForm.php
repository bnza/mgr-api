<?php

namespace App\Entity\Vocabulary\Pottery;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(
    name: 'pottery_functional_forms',
    schema: 'vocabulary',
)]
#[ApiResource(
    shortName: 'FunctionalForm',
    operations: [
        new GetCollection(
            uriTemplate: '/pottery/functional_forms',
            order: ['value' => 'ASC'],
        ),
        new Get(
            uriTemplate: '/pottery/functional_forms/{id}',
        ),
    ],
    routePrefix: 'vocabulary',
    paginationEnabled: false
)]
class FunctionalForm
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
