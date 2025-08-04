<?php

namespace App\Entity\Data\Join;

use ApiPlatform\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Post;
use App\Entity\Data\Context;
use App\Entity\Data\StratigraphicUnit;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\SequenceGenerator;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity]
#[ORM\Table(
    name: 'context_stratigraphic_units',
)]
#[ORM\UniqueConstraint(columns: ['su_id', 'context_id'])]
#[ApiResource(
    operations: [
        new GetCollection(),
        new GetCollection(
            uriTemplate: '/stratigraphic_units/{parentId}/data_contexts',
            uriVariables: [
                'parentId' => new Link(
                    toProperty: 'stratigraphicUnit',
                    fromClass: StratigraphicUnit::class,
                ),
            ],
            normalizationContext: [
                'groups' => ['context_stratigraphic_unit:stratigraphic_unit:acl:read'],
            ],
        ),
        new GetCollection(
            uriTemplate: '/data_contexts/{parentId}/stratigraphic_units',
            uriVariables: [
                'parentId' => new Link(
                    toProperty: 'context',
                    fromClass: Context::class,
                ),
            ],
            normalizationContext: [
                'groups' => ['context_stratigraphic_unit:contexts:acl:read'],
            ],
        ),
        new Post(),
        new Delete(),
    ],
    normalizationContext: [
        'groups' => ['context_stratigraphic_unit:acl:read'],
    ],
)]
#[ApiFilter(
    OrderFilter::class,
    properties: ['id', 'context.name', 'context.type..group', 'context.type.value']
)]
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
        'context_stratigraphic_unit:stratigraphic_unit:acl:read',
        'context_stratigraphic_unit:contexts:acl:read',
    ])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: StratigraphicUnit::class)]
    #[ORM\JoinColumn(name: 'su_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    #[Groups([
        'context_stratigraphic_unit:acl:read',
        'context_stratigraphic_unit:stratigraphic_unit:acl:read',
        'context_stratigraphic_unit:contexts:acl:read',
    ])]
    private ?StratigraphicUnit $stratigraphicUnit = null;

    #[ORM\ManyToOne(targetEntity: Context::class, inversedBy: 'contextsStratigraphicUnits')]
    #[ORM\JoinColumn(name: 'context_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    #[Groups([
        'context_stratigraphic_unit:acl:read',
        'context_stratigraphic_unit:stratigraphic_unit:acl:read',
        'context_stratigraphic_unit:contexts:acl:read',
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
