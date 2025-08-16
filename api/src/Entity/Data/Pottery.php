<?php

namespace App\Entity\Data;

use ApiPlatform\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
use App\Entity\Vocabulary\CulturalContext;
use App\Entity\Vocabulary\Pottery\FunctionalForm;
use App\Entity\Vocabulary\Pottery\FunctionalGroup;
use App\Entity\Vocabulary\Pottery\Shape;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity]
#[ORM\Table(
    name: 'potteries',
)]
#[ApiResource(
    operations: [
        new Get(),
        new GetCollection(),
        new GetCollection(
            uriTemplate: '/stratigraphic_units/{parentId}/potteries',
            uriVariables: [
                'parentId' => new Link(
                    toProperty: 'stratigraphicUnit',
                    fromClass: StratigraphicUnit::class,
                ),
            ]
        ),
    ],
    routePrefix: 'data',
    normalizationContext: ['groups' => ['pottery:acl:read']],
)]
#[ApiFilter(OrderFilter::class, properties: [
    'id',
    'stratigraphicUnit.site.code',
    'inventory',
    'chronologyLower',
    'chronologyUpper',
    'culturalContext.id',
    'shape.value',
    'functionalGroup.value',
    'functionalForm.value',
])]
class Pottery
{
    #[
        ORM\Id,
        ORM\GeneratedValue(strategy: 'SEQUENCE'),
        ORM\Column(type: 'bigint', unique: true)
    ]
    #[Groups([
        'pottery:acl:read',
    ])]
    private int $id;

    #[ORM\ManyToOne(targetEntity: StratigraphicUnit::class, inversedBy: 'potteries')]
    #[ORM\JoinColumn(name: 'stratigraphic_unit_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    #[Groups([
        'pottery:acl:read',
    ])]
    private StratigraphicUnit $stratigraphicUnit;

    #[ORM\Column(type: 'string', unique: true)]
    #[Groups([
        'pottery:acl:read',
    ])]
    private string $inventory;

    #[ORM\ManyToOne(targetEntity: CulturalContext::class)]
    #[ORM\JoinColumn(name: 'cultural_context_id', referencedColumnName: 'id', nullable: true, onDelete: 'RESTRICT')]
    #[Groups([
        'pottery:acl:read',
    ])]
    private ?CulturalContext $culturalContext;

    #[ORM\Column(type: 'integer', nullable: true)]
    #[Groups([
        'pottery:acl:read',
    ])]
    private ?int $chronologyLower;

    #[ORM\Column(type: 'integer', nullable: true)]
    #[Groups([
        'pottery:acl:read',
    ])]
    private ?int $chronologyUpper;

    #[ORM\ManyToOne(targetEntity: Shape::class)]
    #[ORM\JoinColumn(name: 'part_id', referencedColumnName: 'id', nullable: true, onDelete: 'RESTRICT')]
    #[Groups([
        'pottery:acl:read',
    ])]
    private ?Shape $shape;

    #[ORM\ManyToOne(targetEntity: FunctionalGroup::class)]
    #[ORM\JoinColumn(name: 'functional_group_id', referencedColumnName: 'id', nullable: false, onDelete: 'RESTRICT')]
    #[Groups([
        'pottery:acl:read',
    ])]
    private FunctionalGroup $functionalGroup;

    #[ORM\ManyToOne(targetEntity: FunctionalForm::class)]
    #[ORM\JoinColumn(name: 'functional_form_id', referencedColumnName: 'id', nullable: false, onDelete: 'RESTRICT')]
    #[Groups([
        'pottery:acl:read',
    ])]
    private FunctionalForm $functionalForm;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups([
        'pottery:acl:read',
    ])]
    private ?string $notes;

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): Pottery
    {
        $this->id = $id;

        return $this;
    }

    public function getStratigraphicUnit(): StratigraphicUnit
    {
        return $this->stratigraphicUnit;
    }

    public function setStratigraphicUnit(StratigraphicUnit $stratigraphicUnit): Pottery
    {
        $this->stratigraphicUnit = $stratigraphicUnit;

        return $this;
    }

    public function getInventory(): string
    {
        return $this->inventory;
    }

    public function setInventory(string $inventory): Pottery
    {
        $this->inventory = $inventory;

        return $this;
    }

    public function getCulturalContext(): ?CulturalContext
    {
        return $this->culturalContext;
    }

    public function setCulturalContext(?CulturalContext $culturalContext): Pottery
    {
        $this->culturalContext = $culturalContext;

        return $this;
    }

    public function getChronologyLower(): ?int
    {
        return $this->chronologyLower;
    }

    public function setChronologyLower(?int $chronologyLower): Pottery
    {
        $this->chronologyLower = $chronologyLower;

        return $this;
    }

    public function getChronologyUpper(): ?int
    {
        return $this->chronologyUpper;
    }

    public function setChronologyUpper(?int $chronologyUpper): Pottery
    {
        $this->chronologyUpper = $chronologyUpper;

        return $this;
    }

    public function getShape(): ?Shape
    {
        return $this->shape;
    }

    public function setShape(?Shape $shape): Pottery
    {
        $this->shape = $shape;

        return $this;
    }

    public function getFunctionalGroup(): FunctionalGroup
    {
        return $this->functionalGroup;
    }

    public function setFunctionalGroup(FunctionalGroup $functionalGroup): Pottery
    {
        $this->functionalGroup = $functionalGroup;

        return $this;
    }

    public function getFunctionalForm(): FunctionalForm
    {
        return $this->functionalForm;
    }

    public function setFunctionalForm(FunctionalForm $functionalForm): Pottery
    {
        $this->functionalForm = $functionalForm;

        return $this;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): Pottery
    {
        $this->notes = $notes;

        return $this;
    }
}
