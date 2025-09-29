<?php

namespace App\Entity\Data;

use ApiPlatform\Doctrine\Orm\Filter\ExistsFilter;
use ApiPlatform\Doctrine\Orm\Filter\NumericFilter;
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
use App\Doctrine\Filter\Granted\GrantedStratigraphicUnitFilter;
use App\Doctrine\Filter\SearchStratigraphicUnitFilter;
use App\Doctrine\Filter\UnaccentedSearchFilter;
use App\Entity\Data\Join\ContextStratigraphicUnit;
use App\Entity\Data\Join\SampleStratigraphicUnit;
use App\Entity\Data\Zoo\Bone;
use App\Entity\Data\Zoo\Tooth;
use App\Validator as AppAssert;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\SequenceGenerator;
use Doctrine\ORM\Mapping\Table;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[Entity]
#[Table(
    name: 'sus',
)]
#[ORM\UniqueConstraint(columns: ['site_id', 'year', 'number'])]
#[ApiResource(
    operations: [
        new Get(),
        new GetCollection(
            formats: ['csv' => 'text/csv', 'jsonld' => 'application/ld+json'],
        ),
        new GetCollection(
            uriTemplate: '/sites/{parentId}/stratigraphic_units',
            formats: ['csv' => 'text/csv', 'jsonld' => 'application/ld+json'],
            uriVariables: [
                'parentId' => new Link(
                    toProperty: 'site',
                    fromClass: Site::class,
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
            validationContext: ['groups' => ['validation:su:create']],
        ),
    ],
    routePrefix: 'data',
    normalizationContext: ['groups' => ['sus:acl:read']],
    denormalizationContext: ['groups' => ['su:create']],
)]
#[ApiFilter(OrderFilter::class, properties: ['id', 'year', 'number', 'site.code'])]
#[ApiFilter(
    SearchFilter::class,
    properties: [
        'site' => 'exact',
        'stratigraphicUnitContexts.context' => 'exact',
        'stratigraphicUnitSamples.sample' => 'exact',
        'stratigraphicUnitContexts.context.name' => 'ipartial',
    ]
)]
#[ApiFilter(
    NumericFilter::class,
    properties: [
        'number',
        'year',
    ]
)]
#[ApiFilter(
    RangeFilter::class,
    properties: [
        'number',
        'year',
    ]
)]
#[ApiFilter(
    UnaccentedSearchFilter::class,
    properties: [
        'description',
        'interpretation',
    ]
)]
#[ApiFilter(
    ExistsFilter::class,
    properties: [
        'description',
    ]
)]
#[ApiFilter(SearchStratigraphicUnitFilter::class)]
#[ApiFilter(GrantedStratigraphicUnitFilter::class)]
#[UniqueEntity(
    fields: ['site', 'year', 'number'],
    message: 'Duplicate [site, year, number] combination.',
    groups: ['validation:su:create']
)]
class StratigraphicUnit
{
    #[
        ORM\Id,
        ORM\GeneratedValue(strategy: 'SEQUENCE'),
        ORM\Column(type: 'bigint', unique: true)
    ]
    #[SequenceGenerator(sequenceName: 'context_id_seq')]
    #[Groups([
        'sus:acl:read',
        'sus:export',
        'context_stratigraphic_unit:acl:read',
    ])]
    private int $id;

    #[ORM\ManyToOne(targetEntity: Site::class)]
    #[ORM\JoinColumn(name: 'site_id', nullable: false, onDelete: 'RESTRICT')]
    #[Groups([
        'individual:acl:read',
        'microstratigraphic_unit:acl:read',
        'pottery:export',
        'pottery:acl:read',
        'su:create',
        'sus:acl:read',
        'sus:export',
        'zoo_bone:acl:read',
        'zoo_tooth:acl:read',
    ])]
    #[Assert\NotBlank(groups: [
        'validation:su:create',
    ])]
    private Site $site;

    #[ORM\Column(type: 'integer')]
    #[Groups([
        'su:create',
        'sus:acl:read',
        'sus:export',
    ])]
    #[Assert\AtLeastOneOf([
        new Assert\EqualTo(value: 0, groups: ['validation:su:create']),
        new Assert\Sequentially([
            new Assert\GreaterThanOrEqual(value: 2000),
            new AppAssert\IsLessThanOrEqualToCurrentYear(),
        ],
            groups: ['validation:su:create']),
    ],
        groups: ['validation:su:create']
    )]
    private int $year = 0;

    #[ORM\Column(type: 'integer')]
    #[Groups([
        'su:create',
        'sus:acl:read',
        'sus:export',
    ])]
    #[Assert\NotBlank(groups: [
        'validation:su:create',
    ])]
    #[Assert\Positive(groups: [
        'validation:su:create',
    ])]
    private int $number;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups([
        'su:create',
        'sus:acl:read',
        'sus:export',
    ])]
    private string $description;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups([
        'su:create',
        'sus:acl:read',
        'context_stratigraphic_unit:acl:read',
        'sus:export',
    ])]
    private string $interpretation;

    #[ORM\OneToMany(targetEntity: Individual::class, mappedBy: 'stratigraphicUnit')]
    private Collection $individuals;

    #[ORM\OneToMany(targetEntity: MicrostratigraphicUnit::class, mappedBy: 'stratigraphicUnit')]
    private Collection $microstratigraphicUnits;

    #[ORM\OneToMany(targetEntity: Pottery::class, mappedBy: 'stratigraphicUnit')]
    private Collection $potteries;

    #[ORM\OneToMany(targetEntity: ContextStratigraphicUnit::class, mappedBy: 'stratigraphicUnit')]
    private Collection $stratigraphicUnitContexts;

    #[ORM\OneToMany(targetEntity: SampleStratigraphicUnit::class, mappedBy: 'stratigraphicUnit')]
    private Collection $stratigraphicUnitSamples;

    #[ORM\OneToMany(targetEntity: Bone::class, mappedBy: 'stratigraphicUnit')]
    private Collection $zooBones;

    #[ORM\OneToMany(targetEntity: Tooth::class, mappedBy: 'stratigraphicUnit')]
    private Collection $zooTeeth;

    public function __construct()
    {
        $this->individuals = new ArrayCollection();
        $this->microstratigraphicUnits = new ArrayCollection();
        $this->potteries = new ArrayCollection();
        $this->stratigraphicUnitContexts = new ArrayCollection();
        $this->stratigraphicUnitSamples = new ArrayCollection();
        $this->zooBones = new ArrayCollection();
        $this->zooTeeth = new ArrayCollection();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getSite(): Site
    {
        return $this->site;
    }

    public function setSite(Site $site): StratigraphicUnit
    {
        $this->site = $site;

        return $this;
    }

    public function getYear(): int
    {
        return $this->year;
    }

    public function setYear(int $year): StratigraphicUnit
    {
        $this->year = $year;

        return $this;
    }

    public function getNumber(): int
    {
        return $this->number;
    }

    public function setNumber(int $number): StratigraphicUnit
    {
        $this->number = $number;

        return $this;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): StratigraphicUnit
    {
        $this->description = $description;

        return $this;
    }

    public function getInterpretation(): string
    {
        return $this->interpretation;
    }

    public function setInterpretation(string $interpretation): StratigraphicUnit
    {
        $this->interpretation = $interpretation;

        return $this;
    }

    public function getIndividuals(): Collection
    {
        return $this->individuals;
    }

    public function setIndividuals(Collection $individuals): StratigraphicUnit
    {
        $this->individuals = $individuals;

        return $this;
    }

    public function getMicrostratigraphicUnits(): Collection
    {
        return $this->microstratigraphicUnits;
    }

    public function setMicrostratigraphicUnits(Collection $microstratigraphicUnits): StratigraphicUnit
    {
        $this->microstratigraphicUnits = $microstratigraphicUnits;

        return $this;
    }

    public function getPotteries(): Collection
    {
        return $this->potteries;
    }

    public function setPotteries(Collection $potteries): StratigraphicUnit
    {
        $this->potteries = $potteries;

        return $this;
    }

    public function getStratigraphicUnitContexts(): Collection
    {
        return $this->stratigraphicUnitContexts;
    }

    public function setStratigraphicUnitContexts(Collection $stratigraphicUnitContexts): StratigraphicUnit
    {
        $this->stratigraphicUnitContexts = $stratigraphicUnitContexts;

        return $this;
    }

    public function getStratigraphicUnitSamples(): Collection
    {
        return $this->stratigraphicUnitSamples;
    }

    public function setStratigraphicUnitSamples(Collection $stratigraphicUnitSamples): StratigraphicUnit
    {
        $this->stratigraphicUnitSamples = $stratigraphicUnitSamples;

        return $this;
    }

    public function getZooBones(): Collection
    {
        return $this->zooBones;
    }

    public function setZooBones(Collection $zooBones): StratigraphicUnit
    {
        $this->zooBones = $zooBones;

        return $this;
    }

    public function getZooTeeth(): Collection
    {
        return $this->zooTeeth;
    }

    public function setZooTeeth(Collection $zooTeeth): StratigraphicUnit
    {
        $this->zooTeeth = $zooTeeth;

        return $this;
    }

    #[Groups([
        'individual:acl:read',
        'sus:acl:read',
        'context_stratigraphic_unit:acl:read',
        'microstratigraphic_unit:acl:read',
        'pottery:acl:read',
        'pottery:export',
        'stratigraphic_unit_relationship:read',
        'zoo_bone:acl:read',
        'zoo_tooth:acl:read',
    ])]
    #[ApiProperty(required: true)]
    public function getCode(): string
    {
        return sprintf('%s.%s.%u', $this->site->getCode(), substr(0 === $this->year ? '____' : $this->year, -2), $this->number);
    }
}
