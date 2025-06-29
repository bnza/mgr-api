<?php

namespace App\Entity\Vocabulary\StratigraphicUnit;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Table;

#[Entity]
#[Table(
    name: 'su_relationships',
    schema: 'vocabulary'
)]
class Relationship
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
    #[ORM\OneToOne(targetEntity: Relationship::class)]
    #[ORM\JoinColumn(name: 'inverted_by_id', nullable: true, onDelete: 'RESTRICT')]
    private Relationship $invertedBy;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description;

    public function getId(): string
    {
        return $this->id;
    }

    public function setId(string $id): Relationship
    {
        $this->id = $id;

        return $this;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function setValue(string $value): Relationship
    {
        $this->value = $value;

        return $this;
    }

    public function getInvertedBy(): Relationship
    {
        return $this->invertedBy;
    }

    public function setInvertedBy(Relationship $invertedBy): Relationship
    {
        $this->invertedBy = $invertedBy;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): Relationship
    {
        $this->description = $description;

        return $this;
    }

}
