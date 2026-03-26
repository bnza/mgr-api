<?php

namespace App\Entity\Data\View\List;

use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(
    name: 'vw_history_references',
)]
#[ApiResource(
    shortName: 'ListHistoryReference',
    operations: [
        new Get(
            uriTemplate: '/history/references/{id}',
        ),
        new GetCollection(
            uriTemplate: '/history/references',
        ),
    ],
    routePrefix: 'list',
    order: ['value' => 'ASC'],
)]
#[ApiFilter(
    SearchFilter::class,
    properties: [
        'value' => 'ipartial',
    ]
)]
class HistoryReferenceView
{
    #[ORM\Id,
        ORM\GeneratedValue(strategy: 'IDENTITY'),
        ORM\Column(type: 'string', unique: true)]
    public readonly string $id;

    #[ORM\Column(type: 'string')]
    public readonly string $value;
}
