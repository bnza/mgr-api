<?php

namespace App\Entity\Vocabulary;

use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use App\Doctrine\Filter\UnaccentedSearchFilter;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity]
#[ORM\Table(
    name: 'cultural_contexts',
    schema: 'vocabulary'
)]
#[ApiResource(
    shortName: 'VocCulturalContext',
    description: 'Cultural context vocabulary.',
    operations: [
        new GetCollection(
            uriTemplate: '/cultural_contexts',
            order: ['id' => 'ASC'],
        ),
        new Get(
            uriTemplate: '/cultural_contexts/{id}',
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
    #[Groups([
        'pottery:export',
    ])]
    #[ApiProperty(required: true)]
    public string $value;
}
