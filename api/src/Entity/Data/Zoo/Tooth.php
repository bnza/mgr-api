<?php

namespace App\Entity\Data\Zoo;

use ApiPlatform\Doctrine\Orm\Filter\BooleanFilter;
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
use App\Doctrine\Filter\Granted\GrantedParentStratigraphicUnitFilter;
use App\Doctrine\Filter\SearchSiteAndIdFilter;
use App\Entity\Data\Join\Analysis\AnalysisZooBone;
use App\Entity\Data\StratigraphicUnit;
use App\Entity\Vocabulary\Zoo\Bone as VocabularyBone;
use App\Entity\Vocabulary\Zoo\Taxonomy;
use App\Validator as AppAssert;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\SequenceGenerator;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ORM\Table(
    name: 'zoo_teeth',
)]
#[ApiResource(
    shortName: 'ZooTooth',
    operations: [
        new Get(
            uriTemplate: '/zoo/teeth/{id}',
        ),
        new GetCollection(
            uriTemplate: '/zoo/teeth',
            formats: ['jsonld' => 'application/ld+json', 'csv' => 'text/csv'],
        ),
        new GetCollection(
            uriTemplate: '/stratigraphic_units/{parentId}/zoo/teeth',
            formats: ['jsonld' => 'application/ld+json', 'csv' => 'text/csv'],
            uriVariables: [
                'parentId' => new Link(
                    toProperty: 'stratigraphicUnit',
                    fromClass: StratigraphicUnit::class,
                ),
            ]
        ),
        new Post(
            uriTemplate: '/zoo/teeth',
            securityPostDenormalize: 'is_granted("create", object)',
            validationContext: ['groups' => ['validation:zoo_tooth:create']],
        ),
        new Patch(
            uriTemplate: '/zoo/teeth/{id}',
            security: 'is_granted("update", object)',
            validationContext: ['groups' => ['validation:zoo_tooth:create']],
        ),
        new Delete(
            uriTemplate: '/zoo/teeth/{id}',
            security: 'is_granted("delete", object)',
        ),
    ],
    routePrefix: 'data',
    normalizationContext: ['groups' => ['zoo_tooth:acl:read']],
    denormalizationContext: ['groups' => ['zoo_tooth:create']],
)]
#[ApiFilter(OrderFilter::class, properties: [
    'id',
    'stratigraphicUnit.site.code',
    'taxonomy.value',
    'taxonomy.vernacularName',
    'taxonomy.family',
    'taxonomy.class',
    'element.value',
    'connected',
    'endsPreserved',
    'side',
])]
#[ApiFilter(SearchSiteAndIdFilter::class)]
#[ApiFilter(
    SearchFilter::class,
    properties: [
        'stratigraphicUnit.site' => 'exact',
        'stratigraphicUnit' => 'exact',
        'stratigraphicUnit.chronologyLower' => 'exact',
        'stratigraphicUnit.chronologyUpper' => 'exact',
        'taxonomy' => 'exact',
        'element' => 'exact',
        'side' => 'exact',
        'taxonomy.code' => 'exact',
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
    BooleanFilter::class,
    properties: [
        'connected',
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
#[ApiFilter(
    GrantedParentStratigraphicUnitFilter::class
)]
class Tooth
{
    #[
        ORM\Id,
        ORM\GeneratedValue(strategy: 'SEQUENCE'),
        ORM\Column(type: 'bigint', unique: true)
    ]
    #[SequenceGenerator(sequenceName: 'zoo_bone_id_seq')]
    #[Groups([
        'zoo_tooth:acl:read',
    ])]
    private int $id;

    #[ORM\ManyToOne(targetEntity: StratigraphicUnit::class, inversedBy: 'zooTeeth')]
    #[ORM\JoinColumn(name: 'stratigraphic_unit_id', referencedColumnName: 'id', nullable: false, onDelete: 'RESTRICT')]
    #[Groups([
        'zoo_tooth:acl:read',
        'zoo_tooth:create',
    ])]
    #[Assert\NotBlank(groups: [
        'validation:zoo_tooth:create',
    ])]
    #[ApiProperty(required: true)]
    private StratigraphicUnit $stratigraphicUnit;

    /** @var Collection<AnalysisZooBone> */
    #[ORM\OneToMany(
        targetEntity: AnalysisZooBone::class,
        mappedBy: 'subject',
        cascade: ['persist', 'remove'],
        orphanRemoval: true,
    )]
    private Collection $analyses;

    #[ORM\ManyToOne(targetEntity: Taxonomy::class)]
    #[ORM\JoinColumn(name: 'voc_taxonomy_id', referencedColumnName: 'id', nullable: true, onDelete: 'RESTRICT')]
    #[Groups([
        'zoo_tooth:acl:read',
        'zoo_tooth:create',
    ])]
    #[Assert\NotBlank(groups: [
        'validation:zoo_tooth:create',
    ])]
    #[ApiProperty(required: true)]
    private Taxonomy $taxonomy;

    #[ORM\ManyToOne(targetEntity: VocabularyBone::class)]
    #[ORM\JoinColumn(name: 'voc_tooth_id', referencedColumnName: 'id', nullable: true, onDelete: 'RESTRICT')]
    #[Groups([
        'zoo_tooth:acl:read',
        'zoo_tooth:create',
    ])]
    #[Assert\NotBlank(groups: ['validation:zoo_tooth:create'])]
    #[AppAssert\IsValidToothElement(groups: ['validation:zoo_tooth:create'])]
    private ?VocabularyBone $element;

    #[ORM\Column(type: 'boolean')]
    #[Groups([
        'zoo_tooth:acl:read',
        'zoo_tooth:create',
    ])]
    private bool $connected;

    #[ORM\Column(type: 'string', nullable: true, options: ['fixed' => true, 'length' => 1, 'comment' => 'L = left, R = right, ? = indeterminate'])]
    #[Groups([
        'zoo_tooth:acl:read',
        'zoo_tooth:create',
    ])]
    #[Assert\Choice(['L', 'R', '?'], groups: [
        'validation:zoo_tooth:create',
    ])]
    private ?string $side = null;

    #[ORM\Column(type: 'string', nullable: true)]
    #[Groups([
        'zoo_tooth:acl:read',
        'zoo_tooth:create',
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

    public function setStratigraphicUnit(StratigraphicUnit $stratigraphicUnit): Tooth
    {
        $this->stratigraphicUnit = $stratigraphicUnit;

        return $this;
    }

    #[Groups([
        'zoo_tooth:acl:read',
        'zoo_tooth_analysis:acl:read',
    ])]
    public function getCode(): string
    {
        return sprintf('%s.%u', $this->stratigraphicUnit->getSite()->getCode(), $this->getId());
    }

    public function getTaxonomy(): Taxonomy
    {
        return $this->taxonomy;
    }

    public function setTaxonomy(Taxonomy $taxonomy): Tooth
    {
        $this->taxonomy = $taxonomy;

        return $this;
    }

    public function getElement(): ?VocabularyBone
    {
        return $this->element;
    }

    public function setElement(?VocabularyBone $element): Tooth
    {
        $this->element = $element;

        return $this;
    }

    public function getSide(): ?string
    {
        return $this->side;
    }

    public function setSide(?string $side): Tooth
    {
        $this->side = $side ?? null;

        return $this;
    }

    public function isConnected(): bool
    {
        return $this->connected;
    }

    public function setConnected(bool $connected): Tooth
    {
        $this->connected = $connected;

        return $this;
    }

    public function getConnectedCode(): string
    {
        return $this->connected ? 'J' : 'L'; // J = jaw, L = loose
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): Tooth
    {
        $this->notes = $notes ?? null;

        return $this;
    }

    public function getAnalyses(): Collection
    {
        return $this->analyses;
    }

    public function setAnalyses(Collection $analyses): Tooth
    {
        $this->analyses = $analyses;

        return $this;
    }
}
