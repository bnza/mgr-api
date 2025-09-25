<?php

namespace App\Entity\Data;

use ApiPlatform\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ORM\Table(
    name: 'mus',
)]
#[ORM\UniqueConstraint(columns: ['stratigraphic_unit_id', 'identifier'])]
#[ApiResource(
    operations: [
        new Get(),
        new GetCollection(
            formats: ['csv' => 'text/csv', 'jsonld' => 'application/ld+json'],
        ),
        new GetCollection(
            uriTemplate: '/stratigraphic_units/{parentId}/microstratigraphic_units',
            formats: ['csv' => 'text/csv', 'jsonld' => 'application/ld+json'],
            uriVariables: [
                'parentId' => new Link(
                    toProperty: 'stratigraphicUnit',
                    fromClass: StratigraphicUnit::class,
                ),
            ]
        ),
        new Delete(
            security: 'is_granted("delete", object)',
        ),
        new Patch(
            security: 'is_granted("update", object)',
        ),
        new Post(
            securityPostDenormalize: 'is_granted("create", object)',
            validationContext: ['groups' => ['validation:microstratigraphic_unit:create']],
        ),
    ],
    routePrefix: 'data',
    normalizationContext: ['groups' => ['microstratigraphic_unit:acl:read']],
    denormalizationContext: ['groups' => ['microstratigraphic_unit:create']],
)]
#[ApiFilter(OrderFilter::class, properties: [
    'id',
    'stratigraphicUnit.site.code',
    'identifier',
])]
#[UniqueEntity(fields: ['stratigraphicUnit', 'identifier'], groups: ['validation:pottery:create'])]
class MicrostratigraphicUnit
{
    #[
        ORM\Id,
        ORM\GeneratedValue(strategy: 'SEQUENCE'),
        ORM\Column(type: 'bigint', unique: true)
    ]
    #[Groups([
        'microstratigraphic_unit:acl:read',
        'microstratigraphic_unit:export',
    ])]
    private int $id;

    #[ORM\ManyToOne(targetEntity: StratigraphicUnit::class, inversedBy: 'microstratigraphicUnits')]
    #[ORM\JoinColumn(name: 'stratigraphic_unit_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    #[Groups([
        'microstratigraphic_unit:acl:read',
        'microstratigraphic_unit:create',
        'microstratigraphic_unit:export',
    ])]
    #[Assert\NotBlank(groups: [
        'validation:microstratigraphic_unit:create',
    ])]
    private StratigraphicUnit $stratigraphicUnit;

    #[ORM\Column(type: 'string')]
    #[Assert\NotBlank(groups: [
        'validation:microstratigraphic_unit:create',
    ])]
    #[Groups([
        'microstratigraphic_unit:acl:read',
        'microstratigraphic_unit:create',
        'microstratigraphic_unit:export',
    ])]
    private string $identifier;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups([
        'microstratigraphic_unit:acl:read',
        'microstratigraphic_unit:create',
        'microstratigraphic_unit:export',
    ])]
    private string $notes;

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): MicrostratigraphicUnit
    {
        $this->id = $id;

        return $this;
    }

    public function getStratigraphicUnit(): StratigraphicUnit
    {
        return $this->stratigraphicUnit;
    }

    public function setStratigraphicUnit(StratigraphicUnit $stratigraphicUnit): MicrostratigraphicUnit
    {
        $this->stratigraphicUnit = $stratigraphicUnit;

        return $this;
    }

    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    public function setIdentifier(string $identifier): MicrostratigraphicUnit
    {
        $this->identifier = $identifier;

        return $this;
    }

    public function getNotes(): string
    {
        return $this->notes;
    }

    public function setNotes(string $notes): MicrostratigraphicUnit
    {
        $this->notes = $notes;

        return $this;
    }

    #[Groups([
        'microstratigraphic_unit:acl:read',
        'microstratigraphic_unit:export',
    ])]
    public function getCode(): string
    {
        return sprintf(
            '%s.%s',
            $this->getStratigraphicUnit()->getCode(),
            $this->getIdentifier(),
        );
    }
}
