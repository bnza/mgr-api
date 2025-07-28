<?php

namespace App\Entity\Vocabulary;

use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use App\Doctrine\Filter\UnaccentedSearchFilter;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(
    name: 'cultural_contexts',
    schema: 'vocabulary'
)]
#[ApiResource(
    operations: [
        new GetCollection(
            order: ['id' => 'ASC'],
        ),
    ],
    routePrefix: 'vocabulary',
    paginationEnabled: false
)]
#[ApiFilter(
    UnaccentedSearchFilter::class,
    properties: [
        'value',
    ]
)]
class CulturalContext
{
    #[
        ORM\Id,
        ORM\Column(type: 'smallint')
    ]
    public int $id;

    #[ORM\Column(type: 'string', unique: true)]
    public string $value;
}
