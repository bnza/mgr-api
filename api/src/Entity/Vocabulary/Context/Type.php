<?php

namespace App\Entity\Vocabulary\Context;

use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use App\Doctrine\Filter\UnaccentedSearchFilter;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity]
#[ORM\Table(
    name: 'context_types',
    schema: 'vocabulary'
)]
#[ORM\UniqueConstraint(columns: ['type_group', 'value'])]
#[ApiResource(
    shortName: 'ContextType',
    operations: [
        new GetCollection(
            uriTemplate: '/context/types',
            order: ['group' => 'ASC', 'value' => 'ASC'],
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
        'context:acl:read',
        'context_stratigraphic_unit:acl:read',
    ])]
    public string $group;

    #[ORM\Column(type: 'string')]
    #[Groups([
        'context:acl:read',
        'context_stratigraphic_unit:acl:read',
    ])]
    public string $value;
}
