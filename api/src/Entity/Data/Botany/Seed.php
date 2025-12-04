<?php

namespace App\Entity\Data\Botany;

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
use App\Entity\Data\Join\Analysis\AnalysisBotanySeed;
use App\Entity\Data\StratigraphicUnit;
use App\Entity\Vocabulary\Botany\Element as VocabularyElement;
use App\Entity\Vocabulary\Botany\ElementPart;
use App\Entity\Vocabulary\Botany\Taxonomy;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\SequenceGenerator;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ORM\Table(
    name: 'botany_seeds',
)]
#[ApiResource(
    shortName: 'BotanySeed',
    operations: [
        new Get(
            uriTemplate: '/botany/seeds/{id}',
        ),
        new GetCollection(
            uriTemplate: '/botany/seeds',
            formats: ['jsonld' => 'application/ld+json', 'csv' => 'text/csv'],
        ),
        new GetCollection(
            uriTemplate: '/stratigraphic_units/{parentId}/botany/seeds',
            formats: ['jsonld' => 'application/ld+json', 'csv' => 'text/csv'],
            uriVariables: [
                'parentId' => new Link(
                    toProperty: 'stratigraphicUnit',
                    fromClass: StratigraphicUnit::class,
                ),
            ]
        ),
        new Post(
            uriTemplate: '/botany/seeds',
            securityPostDenormalize: 'is_granted("create", object)',
            validationContext: ['groups' => ['validation:botany_seed:create']],
        ),
        new Patch(
            uriTemplate: '/botany/seeds/{id}',
            security: 'is_granted("update", object)',
            validationContext: ['groups' => ['validation:botany_seed:create']],
        ),
        new Delete(
            uriTemplate: '/botany/seeds/{id}',
            security: 'is_granted("delete", object)',
        ),
    ],
    routePrefix: 'data',
    normalizationContext: ['groups' => ['botany_seed:acl:read']],
    denormalizationContext: ['groups' => ['botany_seed:create']],
    order: ['id' => 'DESC'],
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
        'part' => 'exact',
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
#[ApiFilter(
    GrantedParentStratigraphicUnitFilter::class
)]
class Seed
{
    #[
        ORM\Id,
        ORM\GeneratedValue(strategy: 'SEQUENCE'),
        ORM\Column(type: 'bigint', unique: true)
    ]
    #[SequenceGenerator(sequenceName: 'botany_item_id_seq')]
    #[Groups([
        'botany_seed:acl:read',
    ])]
    private int $id;

    #[ORM\ManyToOne(targetEntity: StratigraphicUnit::class, inversedBy: 'botanySeeds')]
    #[ORM\JoinColumn(name: 'stratigraphic_unit_id', referencedColumnName: 'id', nullable: false, onDelete: 'RESTRICT')]
    #[Groups([
        'botany_seed:acl:read',
        'botany_seed:create',
    ])]
    #[Assert\NotBlank(groups: [
        'validation:botany_seed:create',
    ])]
    #[ApiProperty(required: true)]
    private StratigraphicUnit $stratigraphicUnit;

    /** @var Collection<AnalysisBotanySeed> */
    #[ORM\OneToMany(
        targetEntity: AnalysisBotanySeed::class,
        mappedBy: 'subject',
        cascade: ['persist', 'remove'],
        orphanRemoval: true,
    )]
    private Collection $analyses;

    #[ORM\ManyToOne(targetEntity: Taxonomy::class)]
    #[ORM\JoinColumn(name: 'voc_taxonomy_id', referencedColumnName: 'id', nullable: true, onDelete: 'RESTRICT')]
    #[Groups([
        'botany_seed:acl:read',
        'botany_seed:create',
    ])]
    #[Assert\NotBlank(groups: [
        'validation:botany_seed:create',
    ])]
    #[ApiProperty(required: true)]
    private Taxonomy $taxonomy;

    #[ORM\ManyToOne(targetEntity: VocabularyElement::class)]
    #[ORM\JoinColumn(name: 'voc_element_id', referencedColumnName: 'id', nullable: true, onDelete: 'RESTRICT')]
    #[Groups([
        'botany_seed:acl:read',
        'botany_seed:create',
    ])]
    private ?VocabularyElement $element;

    #[ORM\ManyToOne(targetEntity: ElementPart::class)]
    #[ORM\JoinColumn(name: 'voc_element_part_id', referencedColumnName: 'id', nullable: true, onDelete: 'RESTRICT')]
    #[Groups([
        'botany_seed:acl:read',
        'botany_seed:create',
    ])]
    private ?ElementPart $part = null;

    #[ORM\Column(type: 'string', nullable: true)]
    #[Groups([
        'botany_seed:acl:read',
        'botany_seed:create',
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

    public function setStratigraphicUnit(StratigraphicUnit $stratigraphicUnit): Seed
    {
        $this->stratigraphicUnit = $stratigraphicUnit;

        return $this;
    }

    #[Groups([
        'botany_seed:acl:read',
        'botany_seed_analysis:acl:read',
    ])]
    public function getCode(): string
    {
        return sprintf('%s.%u', $this->stratigraphicUnit->getSite()->getCode(), $this->getId());
    }

    public function getTaxonomy(): Taxonomy
    {
        return $this->taxonomy;
    }

    public function setTaxonomy(Taxonomy $taxonomy): Seed
    {
        $this->taxonomy = $taxonomy;

        return $this;
    }

    public function getElement(): ?VocabularyElement
    {
        return $this->element;
    }

    public function setElement(?VocabularyElement $element): Seed
    {
        $this->element = $element;

        return $this;
    }

    public function getPart(): ?ElementPart
    {
        return $this->part;
    }

    public function setPart(?ElementPart $part): Seed
    {
        $this->part = $part;

        return $this;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): Seed
    {
        $this->notes = $notes ?? null;

        return $this;
    }

    public function getAnalyses(): Collection
    {
        return $this->analyses;
    }

    public function setAnalyses(Collection $analyses): Seed
    {
        $this->analyses = $analyses;

        return $this;
    }
}
