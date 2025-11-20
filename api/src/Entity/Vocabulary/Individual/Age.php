<?php

namespace App\Entity\Vocabulary\Individual;

use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(
    name: 'individual_ages',
    schema: 'vocabulary'
)]
#[ORM\UniqueConstraint(columns: ['value'])]
#[ApiResource(
    shortName: 'VocIndividualAge',
    operations: [
        new GetCollection(
            uriTemplate: '/individual/age',
            order: ['id' => 'ASC'],
        ),
        new Get(
            uriTemplate: '/individual/age/{id}',
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
class Age
{
    #[
        ORM\Id,
        ORM\Column(type: 'smallint')
    ]
    public int $id;

    #[ORM\Column(type: 'string')]
    #[ApiProperty(required: true)]
    public string $value;
}
