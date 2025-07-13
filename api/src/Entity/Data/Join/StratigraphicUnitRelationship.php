<?php

namespace App\Entity\Data\Join;

use App\Entity\Data\StratigraphicUnit;
use App\Entity\Vocabulary\StratigraphicUnit\Relationship;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(
    name: 'stratigraphic_units_relationships',
)]
#[ORM\UniqueConstraint(columns: ['lft_su_id', 'rgt_su_id'])]
class StratigraphicUnitRelationship
{
    #[
        ORM\Id,
        ORM\GeneratedValue(strategy: 'SEQUENCE'),
        ORM\Column(type: 'bigint', unique: true)
    ]
    private int $id;

    #[ORM\ManyToOne(targetEntity: StratigraphicUnit::class)]
    #[ORM\JoinColumn(name: 'lft_su_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private StratigraphicUnit $lftStratigraphicUnit;

    #[ORM\ManyToOne(targetEntity: Relationship::class)]
    #[ORM\JoinColumn(name: 'relationship_id', referencedColumnName: 'id', nullable: false, onDelete: 'RESTRICT')]
    private Relationship $relationship;

    #[ORM\ManyToOne(targetEntity: StratigraphicUnit::class)]
    #[ORM\JoinColumn(name: 'rgt_su_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private StratigraphicUnit $rgtStratigraphicUnit;

    public function getId(): int
    {
        return $this->id;
    }

    public function getLftStratigraphicUnit(): StratigraphicUnit
    {
        return $this->lftStratigraphicUnit;
    }

    public function setLftStratigraphicUnit(StratigraphicUnit $lftStratigraphicUnit): StratigraphicUnitRelationship
    {
        $this->lftStratigraphicUnit = $lftStratigraphicUnit;

        return $this;
    }

    public function getRelationship(): Relationship
    {
        return $this->relationship;
    }

    public function setRelationship(Relationship $relationship): StratigraphicUnitRelationship
    {
        $this->relationship = $relationship;

        return $this;
    }

    public function getRgtStratigraphicUnit(): StratigraphicUnit
    {
        return $this->rgtStratigraphicUnit;
    }

    public function setRgtStratigraphicUnit(StratigraphicUnit $rgtStratigraphicUnit): StratigraphicUnitRelationship
    {
        $this->rgtStratigraphicUnit = $rgtStratigraphicUnit;

        return $this;
    }
}
