<?php

namespace App\Entity\Vocabulary\Pottery;

use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity]
#[ORM\Table(
    schema: 'vocabulary'
)]
#[ApiResource(
    shortName: 'VocPotteryDecoration',
    operations: [
        new GetCollection(
            uriTemplate: '/pottery/decorations',
            order: ['id' => 'ASC'],
        ),
        new Get(
            uriTemplate: '/pottery/decorations/{id}',
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
class Decoration
{
    #[
        ORM\Id,
        ORM\Column(type: 'smallint'),
        ORM\GeneratedValue(strategy: 'SEQUENCE'),
    ]
    public int $id;

    #[ORM\Column(type: 'string', unique: true)]
    #[Groups([
        'pottery:export',
    ])]
    #[ApiProperty(required: true)]
    public string $value;
}
