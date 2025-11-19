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
    name: 'vw_botany_taxonomy_classes',
    schema: 'vocabulary'
)]
#[ApiResource(
    shortName: 'ListVocBotanyTaxonomyClass',
    operations: [
        new Get(
            uriTemplate: '/botany/taxonomy_classes/{id}',
        ),
        new GetCollection(
            uriTemplate: '/botany/taxonomy_classes',
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
readonly class VocabularyBotanyClassView
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
