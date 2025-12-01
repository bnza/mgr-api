<?php

namespace App\Entity\Data\View\List;

use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use App\Entity\Data\Site;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(
    name: 'vw_buildings',
)]
#[ApiResource(
    shortName: 'ListBuildings',
    operations: [
        new Get(
            uriTemplate: '/buildings/{id}',
        ),
        new GetCollection(
            uriTemplate: '/buildings',
        ),
    ],
    routePrefix: 'list',
    order: ['value' => 'ASC'],
)]
#[ApiFilter(
    SearchFilter::class,
    properties: [
        'site' => 'exact',
        'area' => 'exact',
        'value' => 'exact',
    ]
)]
readonly class BuildingView
{
    #[
        ORM\Id,
        ORM\GeneratedValue(strategy: 'IDENTITY'),
        ORM\Column(type: 'string', unique: true)
    ]
    #[ApiProperty(required: true)]
    public string $id;

    #[ORM\ManyToOne(targetEntity: Site::class)]
    #[ORM\JoinColumn(name: 'site_id', nullable: false, onDelete: 'RESTRICT')]
    #[ApiProperty(required: true)]
    public Site $site;

    #[ORM\Column(type: 'string')]
    public ?string $area;

    #[ORM\Column(type: 'string')]
    #[ApiProperty(required: true)]
    public string $value;
}
