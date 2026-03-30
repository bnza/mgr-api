<?php

namespace App\Entity\Vocabulary;

use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(
    name: 'centuries',
    schema: 'vocabulary'
)]
#[ApiResource(
    shortName: 'VocCentury',
    description: 'Century vocabulary.',
    operations: [
        new GetCollection(
            uriTemplate: '/centuries',
            order: ['id' => 'ASC'],
        ),
        new Get(
            uriTemplate: '/centuries/{id}',
        ),
    ],
    routePrefix: 'vocabulary',
    paginationEnabled: false
)]
#[ApiFilter(
    SearchFilter::class,
    properties: [
        'value' => 'exact',
    ]
)]
class Century
{
    #[ORM\Id,
        ORM\Column(type: 'smallint')]
    public int $id;

    #[ORM\Column(type: 'string', unique: true)]
    #[ApiProperty(required: true)]
    public string $value;

    #[ApiProperty(required: true)]
    public int $chronologyLower;

    #[ApiProperty(required: true)]
    public int $chronologyUpper;
}
