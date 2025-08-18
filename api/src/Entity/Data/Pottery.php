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
use App\Entity\Vocabulary\CulturalContext;
use App\Entity\Vocabulary\Pottery\FunctionalForm;
use App\Entity\Vocabulary\Pottery\FunctionalGroup;
use App\Entity\Vocabulary\Pottery\Shape;
use App\Validator as AppAssert;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ORM\Table(
    name: 'potteries',
)]
#[ApiResource(
    operations: [
        new Get(),
        new GetCollection(
            formats: ['csv' => 'text/csv', 'jsonld' => 'application/ld+json'],
        ),
        new GetCollection(
            uriTemplate: '/stratigraphic_units/{parentId}/potteries',
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
            validationContext: ['groups' => ['validation:pottery:create']],
        ),
    ],
    routePrefix: 'data',
    normalizationContext: ['groups' => ['pottery:acl:read']],
    denormalizationContext: ['groups' => ['pottery:create']],
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
#[UniqueEntity(fields: ['inventory'], groups: ['validation:pottery:create'])]
class Pottery
{
    #[
        ORM\Id,
        ORM\GeneratedValue(strategy: 'SEQUENCE'),
        ORM\Column(type: 'bigint', unique: true)
    ]
    #[Groups([
        'pottery:acl:read',
        'pottery:create',
        'pottery:export',
    ])]
    private int $id;

    #[ORM\ManyToOne(targetEntity: StratigraphicUnit::class, inversedBy: 'potteries')]
    #[ORM\JoinColumn(name: 'stratigraphic_unit_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    #[Groups([
        'pottery:acl:read',
        'pottery:create',
        'pottery:export',
    ])]
    #[Assert\NotBlank(groups: [
        'validation:su:create',
    ])]
    private StratigraphicUnit $stratigraphicUnit;

    #[ORM\Column(type: 'string', unique: true)]
    #[Groups([
        'pottery:acl:read',
        'pottery:create',
        'pottery:export',
    ])]
    #[Assert\NotBlank(groups: [
        'validation:su:create',
    ])]
    private string $inventory;

    #[ORM\ManyToOne(targetEntity: CulturalContext::class)]
    #[ORM\JoinColumn(name: 'cultural_context_id', referencedColumnName: 'id', nullable: true, onDelete: 'RESTRICT')]
    #[Groups([
        'pottery:acl:read',
        'pottery:create',
        'pottery:export',
    ])]
    private ?CulturalContext $culturalContext;

    #[ORM\Column(type: 'integer', nullable: true)]
    #[Groups([
        'pottery:acl:read',
        'pottery:create',
        'pottery:export',
    ])]
    #[Assert\GreaterThanOrEqual(value: -32768, groups: ['validation:site:create'])]
    #[AppAssert\IsLessThanOrEqualToCurrentYear(groups: ['validation:site:create'])]
    #[Assert\LessThanOrEqual(propertyPath: 'chronologyUpper', groups: ['validation:site:create'])]
    private ?int $chronologyLower;

    #[ORM\Column(type: 'integer', nullable: true)]
    #[Groups([
        'pottery:acl:read',
        'pottery:create',
        'pottery:export',
    ])]
    #[Assert\GreaterThanOrEqual(value: -32768, groups: ['validation:site:create'])]
    #[AppAssert\IsLessThanOrEqualToCurrentYear(groups: ['validation:site:create'])]
    #[Assert\GreaterThanOrEqual(propertyPath: 'chronologyLower', groups: ['validation:site:create'])]
    private ?int $chronologyUpper;

    #[ORM\ManyToOne(targetEntity: Shape::class)]
    #[ORM\JoinColumn(name: 'part_id', referencedColumnName: 'id', nullable: true, onDelete: 'RESTRICT')]
    #[Groups([
        'pottery:acl:read',
        'pottery:create',
        'pottery:export',
    ])]
    private ?Shape $shape;

    #[ORM\ManyToOne(targetEntity: FunctionalGroup::class)]
    #[ORM\JoinColumn(name: 'functional_group_id', referencedColumnName: 'id', nullable: false, onDelete: 'RESTRICT')]
    #[Groups([
        'pottery:acl:read',
        'pottery:create',
        'pottery:export',
    ])]
    #[Assert\NotBlank(groups: [
        'validation:su:create',
    ])]
    private FunctionalGroup $functionalGroup;

    #[ORM\ManyToOne(targetEntity: FunctionalForm::class)]
    #[ORM\JoinColumn(name: 'functional_form_id', referencedColumnName: 'id', nullable: false, onDelete: 'RESTRICT')]
    #[Groups([
        'pottery:acl:read',
        'pottery:create',
        'pottery:export',
    ])]
    #[Assert\NotBlank(groups: [
        'validation:su:create',
    ])]
    private FunctionalForm $functionalForm;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups([
        'pottery:acl:read',
        'pottery:create',
        'pottery:export',
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
