<?php

namespace App\Entity\Vocabulary\Analysis;

use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use App\Doctrine\Filter\SearchHierarchicalVocabularyFilter;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity]
#[ORM\Table(
    name: 'analysis_types',
    schema: 'vocabulary'
)]
#[ORM\UniqueConstraint(columns: ['type_group', 'value'])]
#[ApiResource(
    shortName: 'VocAnalysisType',
    operations: [
        new GetCollection(
            uriTemplate: '/analysis/types',
            order: ['group' => 'ASC', 'value' => 'ASC'],
        ),
        new Get(
            uriTemplate: '/analysis/types/{id}',
        ),
    ],
    routePrefix: 'vocabulary',
    paginationEnabled: false
)]
#[ApiFilter(
    SearchHierarchicalVocabularyFilter::class,
)]
class Type
{
    #[
        ORM\Id,
        ORM\GeneratedValue(strategy: 'SEQUENCE'),
        ORM\Column(type: 'smallint')
    ]
    public int $id;

    #[ORM\Column(name: 'type_group', type: 'string')]
    #[Groups([
        'analysis:acl:read',
        'analysis:export',
    ])]
    public string $group;

    #[ORM\Column(type: 'string')]
    #[Groups([
        'analysis:acl:read',
        'analysis:export',
    ])]
    public string $value;
}
