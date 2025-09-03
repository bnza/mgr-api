<?php

namespace App\Entity\Vocabulary\Zoo;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(
    name: 'zoo_species',
    schema: 'vocabulary'
)]
#[ORM\UniqueConstraint(columns: ['code'])]
#[ORM\UniqueConstraint(columns: ['value'])]
#[ApiResource(
    shortName: 'VocZooSpecies',
    operations: [
        new GetCollection(
            uriTemplate: '/zoo/species',
            order: ['value' => 'ASC'],
        ),
        new Get(
            uriTemplate: '/zoo/species/{id}',
        ),
    ],
    routePrefix: 'vocabulary',
    paginationEnabled: false
)]
class Species
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
    private string $scientificName;

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

    public function setCode(string $code): Species
    {
        $this->code = $code;

        return $this;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function setValue(string $value): Species
    {
        $this->value = $value;

        return $this;
    }

    public function getScientificName(): string
    {
        return $this->scientificName;
    }

    public function setScientificName(string $scientificName): Species
    {
        $this->scientificName = $scientificName;

        return $this;
    }

    public function getClass(): string
    {
        return $this->class;
    }

    public function setClass(string $class): Species
    {
        $this->class = $class;

        return $this;
    }

    public function getFamily(): ?string
    {
        return $this->family;
    }

    public function setFamily(?string $family): Species
    {
        $this->family = $family;

        return $this;
    }
}
