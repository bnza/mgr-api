<?php

namespace App\Entity\Data\Join;

use ApiPlatform\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Post;
use App\Entity\Data\Context;
use App\Entity\Data\StratigraphicUnit;
use App\Validator as AppAssert;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\SequenceGenerator;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ORM\Table(
    name: 'context_stratigraphic_units',
)]
#[ORM\UniqueConstraint(columns: ['su_id', 'context_id'])]
#[ApiResource(
    operations: [
        new Get(),
        new GetCollection(
            formats: ['csv' => 'text/csv', 'jsonld' => 'application/ld+json'],
        ),
        new GetCollection(
            uriTemplate: '/stratigraphic_units/{parentId}/contexts',
            formats: ['csv' => 'text/csv', 'jsonld' => 'application/ld+json'],
            uriVariables: [
                'parentId' => new Link(
                    toProperty: 'stratigraphicUnit',
                    fromClass: StratigraphicUnit::class,
                ),
            ],
            normalizationContext: [
                'groups' => ['context_stratigraphic_unit:contexts:acl:read', 'context:acl:read'],
            ],
        ),
        new GetCollection(
            uriTemplate: '/contexts/{parentId}/stratigraphic_units',
            uriVariables: [
                'parentId' => new Link(
                    toProperty: 'context',
                    fromClass: Context::class,
                ),
            ],
            normalizationContext: [
                'groups' => ['context_stratigraphic_unit:stratigraphic_units:acl:read', 'sus:acl:read'],
            ],
        ),
        new Post(
            securityPostDenormalize: 'is_granted("create", object)',
            validationContext: ['groups' => ['validation:context_stratigraphic_unit:create']],
        ),
        new Delete(),
    ],
    routePrefix: 'data',
    normalizationContext: [
        'groups' => ['context_stratigraphic_unit:acl:read'],
    ],
)]
#[ApiFilter(
    OrderFilter::class,
    properties: ['id', 'context.name', 'context.type.group', 'context.type.value']
)]
#[UniqueEntity(
    fields: ['context', 'stratigraphicUnit'],
    message: 'Duplicate [context, stratigraphic unit] combination.',
    groups: ['validation:context_stratigraphic_unit:create'])
]
#[ApiFilter(
    OrderFilter::class,
    properties: [
        'id',
        // Existing Context-based sorting
        'context.name',
        'context.type.group',
        'context.type.value',
        // Add missing Context sortable property mirroring parent Context resource
        'context.site.code',
        // Mirror StratigraphicUnit sortable properties (excluding id)
        'stratigraphicUnit.year',
        'stratigraphicUnit.number',
        'stratigraphicUnit.site.code',
    ]
)]
#[AppAssert\BelongToTheSameSite(groups: ['validation:context_stratigraphic_unit:create'])]
class ContextStratigraphicUnit
{
    #[
        ORM\Id,
        ORM\GeneratedValue(strategy: 'SEQUENCE'),
        ORM\Column(type: 'bigint', unique: true)
    ]
    #[SequenceGenerator(sequenceName: 'context_id_seq')]
    #[Groups([
        'context_stratigraphic_unit:acl:read',
        'context_stratigraphic_unit:contexts:acl:read',
        'context_stratigraphic_unit:stratigraphic_units:acl:read',
    ])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: StratigraphicUnit::class, inversedBy: 'stratigraphicUnitContexts')]
    #[ORM\JoinColumn(name: 'su_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    #[Groups([
        'context_stratigraphic_unit:acl:read',
        'context_stratigraphic_unit:stratigraphic_units:acl:read',
        'context_stratigraphic_unit:stratigraphic_units:export',
    ])]
    #[Assert\NotBlank(groups: [
        'validation:context_stratigraphic_unit:create',
    ])]
    private ?StratigraphicUnit $stratigraphicUnit = null;

    #[ORM\ManyToOne(targetEntity: Context::class, inversedBy: 'contextStratigraphicUnits')]
    #[ORM\JoinColumn(name: 'context_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    #[Groups([
        'context_stratigraphic_unit:acl:read',
        'context_stratigraphic_unit:contexts:acl:read',
        'context_stratigraphic_unit:contexts:export',
    ])]
    #[Assert\NotBlank(groups: [
        'validation:context_stratigraphic_unit:create',
    ])]
    private ?Context $context = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): ContextStratigraphicUnit
    {
        $this->id = $id;

        return $this;
    }

    public function getStratigraphicUnit(): ?StratigraphicUnit
    {
        return $this->stratigraphicUnit;
    }

    public function setStratigraphicUnit(?StratigraphicUnit $stratigraphicUnit): ContextStratigraphicUnit
    {
        $this->stratigraphicUnit = $stratigraphicUnit;

        return $this;
    }

    public function getContext(): ?Context
    {
        return $this->context;
    }

    public function setContext(?Context $context): ContextStratigraphicUnit
    {
        $this->context = $context;

        return $this;
    }
}
