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
    name: 'vw_analysis_laboratories',
)]
#[ApiResource(
    shortName: 'ListAnalysisLaboratory',
    operations: [
        new Get(
            uriTemplate: '/analyses/laboratories/{id}',
        ),
        new GetCollection(
            uriTemplate: '/analyses/laboratories',
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
readonly class AnalysisLaboratoryView
{
    #[
        ORM\Id,
        ORM\GeneratedValue(strategy: 'IDENTITY'),
        ORM\Column(type: 'string', unique: true)
    ]
    public string $id;

    #[
        ORM\Column(type: 'string')
    ]
    public string $value;
}
