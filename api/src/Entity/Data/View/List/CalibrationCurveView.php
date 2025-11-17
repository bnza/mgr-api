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
    name: 'vw_calibration_curves',
)]
#[ApiResource(
    shortName: 'ListCalibrationCurve',
    operations: [
        new Get(
            uriTemplate: '/calibration_curves/{id}',
        ),
        new GetCollection(
            uriTemplate: '/calibration_curves',
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
readonly class CalibrationCurveView
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
