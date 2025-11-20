<?php

namespace App\Entity\Vocabulary\Botany;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(
    name: 'botany_element_parts',
    schema: 'vocabulary'
)]
#[ORM\UniqueConstraint(columns: ['value'])]
#[ApiResource(
    shortName: 'VocBotanyElementPart',
    operations: [
        new Get(
            uriTemplate: '/botany/element_parts/{id}',
        ),
        new GetCollection(
            uriTemplate: '/botany/element_parts',
            order: ['value' => 'ASC'],
        ),
    ],
    routePrefix: 'vocabulary',
    paginationEnabled: false
)]
class ElementPart
{
    #[
        ORM\Id,
        ORM\GeneratedValue(strategy: 'SEQUENCE'),
        ORM\Column(type: 'smallint')
    ]
    private int $id;

    #[ORM\Column(type: 'string')]
    #[ApiProperty(required: true)]
    private string $value;

    public function getId(): int
    {
        return $this->id;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function setValue(string $value): ElementPart
    {
        $this->value = $value;

        return $this;
    }
}
