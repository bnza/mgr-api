<?php

namespace App\Entity\Vocabulary\Sample;

use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use App\Doctrine\Filter\UnaccentedSearchFilter;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity]
#[ORM\Table(
    name: 'sample_types',
    schema: 'vocabulary'
)]
#[ApiResource(
    shortName: 'SampleType',
    operations: [
        new GetCollection(
            uriTemplate: '/sample/types',
            order: ['value' => 'ASC'],
        ),
        new Get(
            uriTemplate: '/sample/types/{id}',
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

    #[ORM\Column(type: 'string', unique: true)]
    #[Groups([
        'sample_stratigraphic_unit:samples:acl:read',
        'sample:acl:read',
    ])]
    public string $code;

    #[ORM\Column(type: 'string', unique: true)]
    #[Groups([
        'sample_stratigraphic_unit:samples:acl:read',
        'sample:acl:read',
    ])]
    public string $value;
}
