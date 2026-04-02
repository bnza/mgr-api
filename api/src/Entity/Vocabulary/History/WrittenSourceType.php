<?php

namespace App\Entity\Vocabulary\History;

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
    name: 'history_written_source_types',
    schema: 'vocabulary'
)]
#[ORM\UniqueConstraint(columns: ['value'])]
#[ApiResource(
    shortName: 'VocHistoryWrittenSourceType',
    operations: [
        new GetCollection(
            uriTemplate: '/history/written_source_types',
            order: ['value' => 'ASC'],
        ),
        new Get(
            uriTemplate: '/history/written_source_types/{id}',
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
class WrittenSourceType
{
    #[ORM\Id,
        ORM\Column(type: 'smallint'),
        ORM\GeneratedValue(strategy: 'SEQUENCE'),]
    public int $id;

    #[ORM\Column(type: 'string')]
    #[ApiProperty(required: true)]
    #[Groups([
        'history_written_source:acl:read',
        'history_written_source:export',
        'history_written_sources_cited_works:acl:read',
        'history_written_sources_cited_works:export',
    ])]
    public string $value;
}
