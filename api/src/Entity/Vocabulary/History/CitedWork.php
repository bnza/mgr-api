<?php

namespace App\Entity\Vocabulary\History;

use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(
    name: 'history_cited_works',
    schema: 'vocabulary'
)]
#[ORM\UniqueConstraint(columns: ['value'])]
#[ApiResource(
    shortName: 'VocHistoryHistoryCitedWork',
    operations: [
        new GetCollection(
            uriTemplate: '/history/cited_works',
            order: ['value' => 'ASC'],
        ),
        new Get(
            uriTemplate: '/history/cited_works/{id}',
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
class CitedWork
{
    #[ORM\Id,
        ORM\Column(type: 'smallint'),
        ORM\GeneratedValue(strategy: 'SEQUENCE'),]
    public int $id;

    #[ORM\Column(type: 'string')]
    #[ApiProperty(required: true)]
    public string $value;
}
