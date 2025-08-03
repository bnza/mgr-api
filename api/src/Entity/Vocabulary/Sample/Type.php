<?php

namespace App\Entity\Vocabulary\Sample;

use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use App\Doctrine\Filter\UnaccentedSearchFilter;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(
    name: 'sample_types',
    schema: 'vocabulary'
)]
#[ApiResource(
    operations: [
        new GetCollection(
            order: ['value' => 'ASC'],
        ),
    ],
    routePrefix: 'vocabulary/sample',
    paginationEnabled: false
)]
#[ApiFilter(
    UnaccentedSearchFilter::class,
    properties: [
        'value',
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

    #[ORM\Column(type: 'string', unique: true)]
    public string $code;

    #[ORM\Column(type: 'string', unique: true)]
    public string $value;
}
