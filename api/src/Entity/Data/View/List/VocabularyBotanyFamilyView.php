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
    name: 'vw_botany_taxonomy_families',
    schema: 'vocabulary'
)]
#[ApiResource(
    shortName: 'ListVocBotanyTaxonomyFamily',
    operations: [
        new Get(
            uriTemplate: '/botany/taxonomy_families/{id}',
        ),
        new GetCollection(
            uriTemplate: '/botany/taxonomy_families',
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
readonly class VocabularyBotanyFamilyView
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
