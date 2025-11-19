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
    name: 'vw_zoo_taxonomy_families',
    schema: 'vocabulary'
)]
#[ApiResource(
    shortName: 'ListVocZooTaxonomyFamily',
    operations: [
        new Get(
            uriTemplate: '/zoo/taxonomy_families/{id}',
        ),
        new GetCollection(
            uriTemplate: '/zoo/taxonomy_families',
        ),
    ],
    routePrefix: 'list/vocabulary',
    order: ['value' => 'ASC'],
)]
#[ApiFilter(
    SearchFilter::class,
    properties: [
        'value' => 'ipartial',
    ]
)]
readonly class VocabularyZooFamilyView
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
