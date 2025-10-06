<?php

namespace App\Entity\Data\Join;

use ApiPlatform\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use App\Entity\Data\SedimentCore;
use App\Entity\Data\StratigraphicUnit;
use App\Validator as AppAssert;
use BcMath\Number;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ORM\Table(
    name: 'sediment_core_depths',
)]
#[ORM\UniqueConstraint(columns: ['sediment_core_id', 'depth_min'])]
#[ApiResource(
    operations: [
        new Get(),
        new GetCollection(
            formats: ['csv' => 'text/csv', 'jsonld' => 'application/ld+json'],
        ),
        new GetCollection(
            uriTemplate: '/stratigraphic_units/{parentId}/sediment_cores/depths',
            formats: ['csv' => 'text/csv', 'jsonld' => 'application/ld+json'],
            uriVariables: [
                'parentId' => new Link(
                    toProperty: 'stratigraphicUnit',
                    fromClass: StratigraphicUnit::class,
                ),
            ],
            normalizationContext: [
                'groups' => ['sediment_core_depth:sediment_cores:acl:read', 'sediment_core:acl:read'],
            ],
        ),
        new GetCollection(
            uriTemplate: '/sediment_cores/{parentId}/stratigraphic_units/depths',
            formats: ['csv' => 'text/csv', 'jsonld' => 'application/ld+json'],
            uriVariables: [
                'parentId' => new Link(
                    toProperty: 'sedimentCore',
                    fromClass: SedimentCore::class,
                ),
            ],
            normalizationContext: [
                'groups' => ['sediment_core_depth:stratigraphic_units:acl:read', 'sus:acl:read'],
            ],
        ),
        new Post(
            securityPostDenormalize: 'is_granted("create", object)',
            validationContext: ['groups' => ['validation:sediment_core_depth:create']],
        ),
        new Patch(
            security: 'is_granted("update", object)',
        ),
        new Delete(
            security: 'is_granted("delete", object)',
        ),
    ],
    routePrefix: 'data',
    normalizationContext: [
        'groups' => ['sediment_core_depth:acl:read', 'sediment_core:acl:read', 'sus:acl:read'],
    ],
)]
#[ApiFilter(
    OrderFilter::class,
    properties: [
        'id',
        'depthMin',
        'depthMax',
        // Mirror SedimentCore sortable properties (excluding id)
        'sedimentCore.year',
        'sedimentCore.number',
        'sedimentCore.site.code',
        // Mirror StratigraphicUnit sortable properties (excluding id)
        'stratigraphicUnit.year',
        'stratigraphicUnit.number',
        'stratigraphicUnit.site.code',
    ],
)]
#[UniqueEntity(
    fields: ['sedimentCore', 'depthMin'],
    message: 'Duplicate [sediment core, min depth] combination.',
    groups: ['validation:sediment_core_depth:create']
)]
#[AppAssert\BelongToTheSameSite(groups: ['validation:sediment_core_depth:create'])]
class SedimentCoreDepth
{
    #[
        ORM\Id,
        ORM\GeneratedValue(strategy: 'SEQUENCE'),
        ORM\Column(type: 'bigint', unique: true)
    ]
    #[Groups([
        'sediment_core_depth:acl:read',
        'sediment_core_depth:stratigraphic_units:acl:read',
        'sediment_core_depth:sediment_cores:acl:read',
    ])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: SedimentCore::class, inversedBy: 'sedimentCoresStratigraphicUnits')]
    #[ORM\JoinColumn(name: 'sediment_core_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    #[Groups([
        'sediment_core_depth:acl:read',
        'sediment_core_depth:sediment_cores:acl:read',
        'sediment_core_depth:sediment_cores:export',
    ])]
    #[Assert\NotBlank(groups: ['validation:sediment_core_depth:create'])]
    #[ApiProperty(required: true)]
    private ?SedimentCore $sedimentCore = null;

    #[ORM\ManyToOne(targetEntity: StratigraphicUnit::class, inversedBy: 'stratigraphicUnitSedimentCores')]
    #[ORM\JoinColumn(name: 'su_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    #[Groups([
        'sediment_core_depth:acl:read',
        'sediment_core_depth:stratigraphic_units:acl:read',
        'sediment_core_depth:stratigraphic_units:export',
    ])]
    #[Assert\NotBlank(groups: ['validation:sediment_core_depth:create'])]
    #[ApiProperty(required: true)]
    private ?StratigraphicUnit $stratigraphicUnit = null;

    #[ORM\Column(type: 'number', precision: 5, scale: 1)]
    #[Groups([
        'sediment_core_depth:acl:read',
        'sediment_core_depth:sediment_cores:acl:read',
        'sediment_core_depth:stratigraphic_units:acl:read',
    ])]
    #[Assert\NotBlank(groups: ['validation:sediment_core_depth:create'])]
    #[Assert\LessThan(propertyPath: 'depthMax', groups: ['validation:sediment_core_depth:create'])]
    #[Assert\LessThan(value: 10000, groups: ['validation:sediment_core_depth:create'])]
    #[ApiProperty(
        required: true,
        schema: [
            'type' => 'string',
            'pattern' => '^[0-9]{1,4}(\.[0-9]+)?$',
            'example' => '8.5',
        ]
    )]
    private Number $depthMin;

    #[ORM\Column(type: 'number', precision: 5, scale: 1)]
    #[Groups([
        'sediment_core_depth:acl:read',
        'sediment_core_depth:sediment_cores:acl:read',
        'sediment_core_depth:stratigraphic_units:acl:read',
    ])]
    #[Assert\NotBlank(groups: ['validation:sediment_core_depth:create'])]
    #[Assert\GreaterThan(propertyPath: 'depthMin', groups: ['validation:sediment_core_depth:create'])]
    #[Assert\LessThan(value: 10000, groups: ['validation:sediment_core_depth:create'])]
    #[ApiProperty(
        required: true,
        schema: [
            'type' => 'string',
            'pattern' => '^[0-9]{1,4}(\.[0-9]+)?$',
            'example' => '9.0',
        ]
    )]
    private Number $depthMax;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups([
        'sediment_core_depth:acl:read',
        'sediment_core_depth:sediment_cores:acl:read',
        'sediment_core_depth:stratigraphic_units:acl:read',
    ])]
    private ?string $notes = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSedimentCore(): ?SedimentCore
    {
        return $this->sedimentCore;
    }

    public function setSedimentCore(?SedimentCore $sedimentCore): SedimentCoreDepth
    {
        $this->sedimentCore = $sedimentCore;

        return $this;
    }

    public function getStratigraphicUnit(): ?StratigraphicUnit
    {
        return $this->stratigraphicUnit;
    }

    public function setStratigraphicUnit(?StratigraphicUnit $stratigraphicUnit): SedimentCoreDepth
    {
        $this->stratigraphicUnit = $stratigraphicUnit;

        return $this;
    }

    public function getDepthMin(): Number
    {
        return $this->depthMin;
    }

    public function setDepthMin(Number|string $depthMin): SedimentCoreDepth
    {
        if (is_string($depthMin)) {
            $depthMin = new Number($depthMin);
        }
        $this->depthMin = $depthMin;

        return $this;
    }

    public function getDepthMax(): Number
    {
        return $this->depthMax;
    }

    public function setDepthMax(Number|string $depthMax): SedimentCoreDepth
    {
        if (is_string($depthMax)) {
            $depthMax = new Number($depthMax);
        }
        $this->depthMax = $depthMax;

        return $this;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): SedimentCoreDepth
    {
        $this->notes = $notes;

        return $this;
    }

    #[Groups([
        'sediment_core_depth:acl:read',
    ])]
    public function getCode(): string
    {
        return sprintf(
            '%s.%s',
            $this->getSedimentCore()->getCode(),
            $this->depthMin->mul(10)->round()
        );
    }
}
