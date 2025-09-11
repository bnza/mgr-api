<?php

namespace App\Entity\Vocabulary\Zoo;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(
    name: 'zoo_taxonomy',
    schema: 'vocabulary'
)]
#[ORM\UniqueConstraint(columns: ['code'])]
#[ORM\UniqueConstraint(columns: ['value'])]
#[ApiResource(
    shortName: 'VocZooTaxonomy',
    operations: [
        new GetCollection(
            uriTemplate: '/zoo/taxonomy',
            order: ['value' => 'ASC'],
        ),
        new Get(
            uriTemplate: '/zoo/taxonomy/{id}',
        ),
    ],
    routePrefix: 'vocabulary',
    paginationEnabled: false
)]
class Taxonomy
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
    private string $value;

    #[ORM\Column(type: 'string')]
    private string $vernacularName;

    #[ORM\Column(type: 'string')]
    private string $class;
    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $family = null;

    public function getId(): int
    {
        return $this->id;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function setCode(string $code): Taxonomy
    {
        $this->code = $code;

        return $this;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function setValue(string $value): Taxonomy
    {
        $this->value = $value;

        return $this;
    }

    public function getVernacularName(): string
    {
        return $this->vernacularName;
    }

    public function setVernacularName(string $vernacularName): Taxonomy
    {
        $this->vernacularName = $vernacularName;

        return $this;
    }

    public function getClass(): string
    {
        return $this->class;
    }

    public function setClass(string $class): Taxonomy
    {
        $this->class = $class;

        return $this;
    }

    public function getFamily(): ?string
    {
        return $this->family;
    }

    public function setFamily(?string $family): Taxonomy
    {
        $this->family = $family;

        return $this;
    }
}
