<?php

namespace App\Entity\Data\View;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Post;
use App\Entity\Data\StratigraphicUnit;
use App\Entity\Vocabulary\StratigraphicUnit\Relation;
use App\Validator as AppAssert;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ORM\Table(
    name: 'vw_stratigraphic_units_relationships',
)]
#[ApiResource(
    shortName: 'StratigraphicUnitRelationship',
    operations: [
        new Get(),
        new GetCollection(),
        new GetCollection(
            uriTemplate: '/stratigraphic_units/{parentId}/relationships',
            uriVariables: [
                'parentId' => new Link(
                    toProperty: 'lftStratigraphicUnit',
                    fromClass: StratigraphicUnit::class,
                ),
            ],
            paginationEnabled: false,
        ),
        new Post(
            denormalizationContext: ['groups' => ['su_relationship:create']],
            securityPostDenormalize: 'is_granted("create", object)',
            validationContext: ['groups' => ['validation:su_relationship:create']],
        ),
        new Delete(
            security: 'is_granted("delete", object)',
        ),
    ],
    routePrefix: 'data',
    normalizationContext: ['groups' => ['stratigraphic_unit_relationship:read']],
)]
#[AppAssert\BelongToTheSameSite(groups: ['validation:su_relationship:create'])]
class StratigraphicUnitRelationshipView
{
    #[
        ORM\Id,
        ORM\GeneratedValue(strategy: 'IDENTITY'),
        ORM\Column(type: 'bigint', unique: true)
    ]
    private int $id;

    #[ORM\ManyToOne(targetEntity: StratigraphicUnit::class)]
    #[ORM\JoinColumn(name: 'lft_su_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    #[Groups([
        'stratigraphic_unit_relationship:read',
        'su_relationship:create',
    ])]
    #[Assert\NotBlank(groups: [
        'validation:su_relationship:create',
    ])]
    #[ApiProperty(required: true)]
    private ?StratigraphicUnit $lftStratigraphicUnit = null;

    #[ORM\ManyToOne(targetEntity: Relation::class)]
    #[ORM\JoinColumn(name: 'relationship_id', referencedColumnName: 'id', nullable: false, onDelete: 'RESTRICT')]
    #[Groups([
        'stratigraphic_unit_relationship:read',
        'su_relationship:create',
    ])]
    #[Assert\NotBlank(groups: [
        'validation:su_relationship:create',
    ])]
    #[ApiProperty(required: true)]
    private Relation $relationship;

    #[ORM\ManyToOne(targetEntity: StratigraphicUnit::class)]
    #[ORM\JoinColumn(name: 'rgt_su_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    #[Groups([
        'stratigraphic_unit_relationship:read',
        'su_relationship:create',
    ])]
    #[Assert\NotBlank(groups: [
        'validation:su_relationship:create',
    ])]
    #[Assert\NotEqualTo(
        propertyPath: 'lftStratigraphicUnit',
        message: 'Self referencing relationship is not allowed.',
        groups: ['validation:su_relationship:create'])
    ]
    #[ApiProperty(required: true)]
    private ?StratigraphicUnit $rgtStratigraphicUnit = null;

    public function getId(): ?int
    {
        return $this->id ?? null;
    }

    public function getLftStratigraphicUnit(): ?StratigraphicUnit
    {
        return $this->lftStratigraphicUnit;
    }

    public function setLftStratigraphicUnit(StratigraphicUnit $lftStratigraphicUnit): StratigraphicUnitRelationshipView
    {
        $this->lftStratigraphicUnit = $lftStratigraphicUnit;

        return $this;
    }

    public function getRelationship(): Relation
    {
        return $this->relationship;
    }

    public function setRelationship(Relation $relationship): StratigraphicUnitRelationshipView
    {
        $this->relationship = $relationship;

        return $this;
    }

    public function getRgtStratigraphicUnit(): ?StratigraphicUnit
    {
        return $this->rgtStratigraphicUnit;
    }

    public function setRgtStratigraphicUnit(StratigraphicUnit $rgtStratigraphicUnit): StratigraphicUnitRelationshipView
    {
        $this->rgtStratigraphicUnit = $rgtStratigraphicUnit;

        return $this;
    }
}
