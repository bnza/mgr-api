<?php

namespace App\Entity\Vocabulary\StratigraphicUnit;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Table;

#[Entity]
#[Table(
    name: 'su_relationships',
    schema: 'vocabulary'
)]
#[ApiResource(
    shortName: 'StratigraphicUnitRelation',
    operations: [
        new GetCollection(
            uriTemplate: '/stratigraphic_unit/relationships',
            order: ['value' => 'ASC'],
        ),
        new Get(
            uriTemplate: '/stratigraphic_unit/relationships/{id}',
        ),
    ],
    routePrefix: 'vocabulary',
    paginationEnabled: false
)]
class Relation
{
    #[
        ORM\Id,
        ORM\Column(
            type: 'string',
            length: 1,
            unique: true,
            options: [
                'fixed' => true,
            ])
    ]
    private string $id;

    #[ORM\Column(type: 'string', unique: true)]
    private string $value;
    #[ORM\OneToOne(targetEntity: Relation::class)]
    #[ORM\JoinColumn(name: 'inverted_by_id', nullable: true, onDelete: 'RESTRICT')]
    private Relation $invertedBy;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description;

    public function getId(): string
    {
        return $this->id;
    }

    public function setId(string $id): Relation
    {
        $this->id = $id;

        return $this;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function setValue(string $value): Relation
    {
        $this->value = $value;

        return $this;
    }

    public function getInvertedBy(): Relation
    {
        return $this->invertedBy;
    }

    public function setInvertedBy(Relation $invertedBy): Relation
    {
        $this->invertedBy = $invertedBy;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): Relation
    {
        $this->description = $description;

        return $this;
    }
}
