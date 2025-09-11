<?php

namespace App\Entity\Data\Zoo;

use ApiPlatform\Doctrine\Orm\Filter\ExistsFilter;
use ApiPlatform\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Doctrine\Orm\Filter\RangeFilter;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use App\Doctrine\Filter\BitmapFilter;
use App\Doctrine\Filter\SearchZooBoneFilter;
use App\Entity\Data\Join\ZooBoneAnalysis;
use App\Entity\Data\StratigraphicUnit;
use App\Entity\Vocabulary\Zoo\Bone as VocabularyBone;
use App\Entity\Vocabulary\Zoo\BonePart;
use App\Entity\Vocabulary\Zoo\Taxonomy;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ORM\Table(
    name: 'zoo_bones',
)]
#[ApiResource(
    shortName: 'ZooBone',
    operations: [
        new Get(
            uriTemplate: '/zoo/bones/{id}',
        ),
        new GetCollection(
            uriTemplate: '/zoo/bones',
        ),
        new GetCollection(
            uriTemplate: '/stratigraphic_units/{parentId}/zoo/bones',
            uriVariables: [
                'parentId' => new Link(
                    toProperty: 'stratigraphicUnit',
                    fromClass: StratigraphicUnit::class,
                ),
            ]
        ),
        new Post(
            uriTemplate: '/zoo/bones',
            securityPostDenormalize: 'is_granted("create", object)',
            validationContext: ['groups' => ['validation:zoo_bone:create']],
        ),
        new Patch(
            uriTemplate: '/zoo/bones/{id}',
            security: 'is_granted("update", object)',
            validationContext: ['groups' => ['validation:zoo_bone:create']],
        ),
        new Delete(
            uriTemplate: '/zoo/bones/{id}',
            security: 'is_granted("delete", object)',
        ),
    ],
    routePrefix: 'data',
    normalizationContext: ['groups' => ['zoo_bone:acl:read']],
    denormalizationContext: ['groups' => ['zoo_bone:create']],
)]
#[ApiFilter(OrderFilter::class, properties: [
    'id',
    'stratigraphicUnit.site.code',
    'taxonomy.value',
    'taxonomy.vernacularName',
    'taxonomy.family',
    'taxonomy.class',
    'element.value',
    'endsPreserved',
    'side',
])]
#[ApiFilter(SearchZooBoneFilter::class, properties: ['search'])]
#[ApiFilter(
    SearchFilter::class,
    properties: [
        'stratigraphicUnit.site' => 'exact',
        'stratigraphicUnit' => 'exact',
        'stratigraphicUnit.culturalContext' => 'exact',
        'stratigraphicUnit.chronologyLower' => 'exact',
        'stratigraphicUnit.chronologyUpper' => 'exact',
        'taxonomy' => 'exact',
        'element' => 'exact',
        'part' => 'exact',
        'side' => 'exact',
        'taxonomy.family' => 'exact',
        'taxonomy.class' => 'exact',
        'taxonomy.vernacularName' => 'ipartial',
    ]
)]
#[ApiFilter(
    RangeFilter::class,
    properties: [
        'stratigraphicUnit.number',
        'stratigraphicUnit.year',
        'stratigraphicUnit.chronologyLower',
        'stratigraphicUnit.chronologyUpper',
    ]
)]
#[ApiFilter(
    ExistsFilter::class,
    properties: [
        'notes',
        'stratigraphicUnit.chronologyLower',
        'stratigraphicUnit.chronologyUpper',
        'element',
        'part',
    ]
)]
#[ApiFilter(BitmapFilter::class, properties: [
    'endsPreserved',
])]
class Bone
{
    public const int ELEMENT_TYPE_END_DISTAL = 0b01;
    public const int ELEMENT_TYPE_END_PROXIMAL = 0b10;

    #[
        ORM\Id,
        ORM\GeneratedValue(strategy: 'SEQUENCE'),
        ORM\Column(type: 'bigint', unique: true)
    ]
    #[Groups([
        'zoo_bone:acl:read',
    ])]
    private int $id;

    #[ORM\ManyToOne(targetEntity: StratigraphicUnit::class, inversedBy: 'zooBones')]
    #[ORM\JoinColumn(name: 'stratigraphic_unit_id', referencedColumnName: 'id', nullable: false, onDelete: 'RESTRICT')]
    #[Groups([
        'zoo_bone:acl:read',
        'zoo_bone:create',
    ])]
    #[Assert\NotBlank(groups: [
        'validation:zoo_bone:create',
    ])]
    #[ApiProperty(required: true)]
    private StratigraphicUnit $stratigraphicUnit;

    /** @var Collection<ZooBoneAnalysis> */
    #[ORM\OneToMany(
        targetEntity: ZooBoneAnalysis::class,
        mappedBy: 'item',
        cascade: ['persist', 'remove'],
        orphanRemoval: true,
    )]
    private Collection $analyses;

    #[ORM\ManyToOne(targetEntity: Taxonomy::class)]
    #[ORM\JoinColumn(name: 'voc_taxonomy_id', referencedColumnName: 'id', nullable: true, onDelete: 'RESTRICT')]
    #[Groups([
        'zoo_bone:acl:read',
        'zoo_bone:create',
    ])]
    #[Assert\NotBlank(groups: [
        'validation:zoo_bone:create',
    ])]
    #[ApiProperty(required: true)]
    private Taxonomy $taxonomy;

    #[ORM\ManyToOne(targetEntity: VocabularyBone::class)]
    #[ORM\JoinColumn(name: 'voc_bone_id', referencedColumnName: 'id', nullable: true, onDelete: 'RESTRICT')]
    #[Groups([
        'zoo_bone:acl:read',
        'zoo_bone:create',
    ])]
    private ?VocabularyBone $element;

    #[ORM\ManyToOne(targetEntity: BonePart::class)]
    #[ORM\JoinColumn(name: 'voc_bone_part_id', referencedColumnName: 'id', nullable: true, onDelete: 'RESTRICT')]
    #[Groups([
        'zoo_bone:acl:read',
        'zoo_bone:create',
    ])]
    private ?BonePart $part = null;

    #[ORM\Column(type: 'smallint', nullable: true)]
    #[Groups([
        'zoo_bone:acl:read',
        'zoo_bone:create',
    ])]
    private ?int $endsPreserved = null;

    #[ORM\Column(type: 'string', nullable: true, options: ['fixed' => true, 'length' => 1, 'comment' => 'L = left, R = right, ? = indeterminate'])]
    #[Groups([
        'zoo_bone:acl:read',
        'zoo_bone:create',
    ])]
    #[Assert\Choice(['L', 'R', '?'], groups: [
        'validation:zoo_bone:create',
    ])]
    private ?string $side = null;

    #[ORM\Column(type: 'string', nullable: true)]
    #[Groups([
        'zoo_bone:acl:read',
        'zoo_bone:create',
    ])]
    private ?string $notes = null;

    public function __construct()
    {
        $this->analyses = new ArrayCollection();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getStratigraphicUnit(): StratigraphicUnit
    {
        return $this->stratigraphicUnit;
    }

    public function setStratigraphicUnit(StratigraphicUnit $stratigraphicUnit): Bone
    {
        $this->stratigraphicUnit = $stratigraphicUnit;

        return $this;
    }

    #[Groups([
        'zoo_bone:acl:read',
        'zoo_bone_analysis:acl:read',
        'zoo_bone:create',
    ])]
    public function getCode(): string
    {
        return sprintf('%s.%u', $this->stratigraphicUnit->getSite()->getCode(), $this->getId());
    }

    public function getTaxonomy(): Taxonomy
    {
        return $this->taxonomy;
    }

    public function setTaxonomy(Taxonomy $taxonomy): Bone
    {
        $this->taxonomy = $taxonomy;

        return $this;
    }

    public function getElement(): ?VocabularyBone
    {
        return $this->element;
    }

    public function setElement(?VocabularyBone $element): Bone
    {
        $this->element = $element;

        return $this;
    }

    public function getPart(): ?BonePart
    {
        return $this->part;
    }

    public function setPart(?BonePart $part): Bone
    {
        $this->part = $part;

        return $this;
    }

    public function getEndsPreserved(): ?int
    {
        return $this->endsPreserved;
    }

    public function setEndsPreserved(?int $endsPreserved): Bone
    {
        $this->endsPreserved = $endsPreserved ?? null; // 0 is converted to null

        return $this;
    }

    public function getSide(): ?string
    {
        return $this->side;
    }

    public function setSide(?string $side): Bone
    {
        $this->side = $side ?? null; // '' is converted to null

        return $this;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): Bone
    {
        $this->notes = $notes ?? null;

        return $this;
    }

    public function getAnalyses(): Collection
    {
        return $this->analyses;
    }

    public function setAnalyses(Collection $analyses): Bone
    {
        $this->analyses = $analyses;

        return $this;
    }
}
