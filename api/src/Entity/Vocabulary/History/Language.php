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
    name: 'history_languages',
    schema: 'vocabulary'
)]
#[ORM\UniqueConstraint(columns: ['value'])]
#[ApiResource(
    shortName: 'VocHistoryLanguage',
    operations: [
        new GetCollection(
            uriTemplate: '/history/languages',
            order: ['value' => 'ASC'],
        ),
        new Get(
            uriTemplate: '/history/languages/{id}',
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
class Language
{
    #[
        ORM\Id,
        ORM\Column(type: 'smallint'),
        ORM\GeneratedValue(strategy: 'SEQUENCE'),
    ]
    public int $id;

    #[ORM\Column(type: 'string')]
    #[ApiProperty(required: true)]
    #[Groups([
        'history_animal:acl:read',
        'history_plant:acl:read',
        'history_animal:export',
        'history_plant:export',
    ])]
    public string $value;
}
