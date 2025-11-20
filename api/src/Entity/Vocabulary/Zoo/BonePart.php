<?php

namespace App\Entity\Vocabulary\Zoo;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(
    name: 'zoo_bone_parts',
    schema: 'vocabulary'
)]
#[ORM\UniqueConstraint(columns: ['code'])]
#[ORM\UniqueConstraint(columns: ['value'])]
#[ApiResource(
    shortName: 'VocZooBonePart',
    operations: [
        new GetCollection(
            uriTemplate: '/zoo/bone_parts',
            order: ['value' => 'ASC'],
        ),
        new Get(
            uriTemplate: '/zoo/bone_parts/{id}',
        ),
    ],
    routePrefix: 'vocabulary',
    paginationEnabled: false
)]
class BonePart
{
    #[
        ORM\Id,
        ORM\GeneratedValue(strategy: 'SEQUENCE'),
        ORM\Column(type: 'smallint')
    ]
    private int $id;

    #[ORM\Column(type: 'string')]
    private string $code;

    #[ORM\Column(type: 'string')]
    #[ApiProperty(required: true)]
    private string $value;

    public function getId(): int
    {
        return $this->id;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function setCode(string $code): BonePart
    {
        $this->code = $code;

        return $this;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function setValue(string $value): BonePart
    {
        $this->value = $value;

        return $this;
    }
}
