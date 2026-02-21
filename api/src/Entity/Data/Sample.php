<?php

namespace App\Entity\Data;

use ApiPlatform\Doctrine\Orm\Filter\ExistsFilter;
use ApiPlatform\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Doctrine\Orm\Filter\RangeFilter;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use App\Doctrine\Filter\Granted\GrantedParentSiteFilter;
use App\Doctrine\Filter\SearchSampleFilter;
use App\Doctrine\Filter\UnaccentedSearchFilter;
use App\Entity\Data\Join\Analysis\AnalysisSampleMicrostratigraphy;
use App\Entity\Data\Join\SampleStratigraphicUnit;
use App\Entity\Vocabulary\Sample\Type;
use App\Metadata\Attribute\SubResourceFilters\ApiAnalysisSubresourceFilters;
use App\Metadata\Attribute\SubResourceFilters\ApiStratigraphicUnitSubresourceFilters;
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
    name: 'samples',
)]
#[ORM\UniqueConstraint(columns: ['site_id', 'type_id', 'year', 'number'])]
#[ORM\HasLifecycleCallbacks]
#[ApiResource(
    operations: [
        new Get(),
        new GetCollection(
            formats: ['jsonld' => 'application/ld+json', 'csv' => 'text/csv'],
        ),
        new GetCollection(
            uriTemplate: '/sites/{parentId}/samples',
            formats: ['jsonld' => 'application/ld+json', 'csv' => 'text/csv'],
            uriVariables: [
                'parentId' => new Link(
                    toProperty: 'site',
                    fromClass: ArchaeologicalSite::class,
                ),
            ]
        ),
        new Post(
            securityPostDenormalize: 'is_granted("create", object)',
            validationContext: ['groups' => ['validation:sample:create']],
        ),
        new Patch(
            security: 'is_granted("update", object)',
        ),
        new Delete(
            security: 'is_granted("delete", object)',
        ),
    ],
    routePrefix: 'data',
    normalizationContext: ['groups' => ['sample:acl:read']],
    order: ['id' => 'DESC'],
)]
#[ApiFilter(
    OrderFilter::class,
    properties: ['id', 'site.code', 'year', 'number', 'type.code', 'type.value']
)]
#[ApiFilter(
    SearchFilter::class,
    properties: [
        'site' => 'exact',
        'type' => 'exact',
        'number' => 'exact',
        'year' => 'exact',
        'sampleStratigraphicUnits.stratigraphicUnit' => 'exact',
    ]
)]
#[ApiFilter(
    RangeFilter::class,
    properties: [
        'year',
        'number',
    ]
)]
#[ApiFilter(
    ExistsFilter::class,
    properties: [
        'year',
        'description',
        'sampleStratigraphicUnits',
        'analysesMicrostratigraphicUnits',
    ]
)]
#[ApiFilter(
    UnaccentedSearchFilter::class,
    properties: [
        'description',
    ]
)]
#[ApiFilter(SearchSampleFilter::class, properties: ['search'])]
#[ApiFilter(GrantedParentSiteFilter::class)]
#[ApiAnalysisSubresourceFilters('analysesMicrostratigraphicUnits.analysis')]
#[ApiStratigraphicUnitSubresourceFilters('sampleStratigraphicUnits.stratigraphicUnit')]
#[UniqueEntity(
    fields: ['site', 'type', 'year', 'number'],
    message: 'Duplicate [site, type, year, number] combination.',
    groups: ['validation:sample:create']
)]
class Sample
{
    #[
        ORM\Id,
        ORM\GeneratedValue(strategy: 'SEQUENCE'),
        ORM\Column(type: 'bigint', unique: true)
    ]
    #[SequenceGenerator(sequenceName: 'context_id_seq')]
    #[Groups([
        'sample:acl:read',
        'sample:export',
    ])]
    private int $id;

    #[ORM\ManyToOne(targetEntity: ArchaeologicalSite::class)]
    #[ORM\JoinColumn(name: 'site_id', nullable: false, onDelete: 'RESTRICT')]
    #[Groups([
        'sample:acl:read',
        'sample:export',
    ])]
    #[Assert\NotBlank(groups: [
        'validation:sample:create',
    ])]
    private ArchaeologicalSite $site;

    #[ORM\ManyToOne(targetEntity: Type::class)]
    #[ORM\JoinColumn(name: 'type_id', nullable: false, onDelete: 'RESTRICT')]
    #[Groups([
        'sample:acl:read',
        'sample:export',
    ])]
    #[Assert\NotBlank(groups: [
        'validation:sample:create',
    ])]
    private Type $type;

    #[ORM\Column(type: 'smallint')]
    #[Groups([
        'sample:acl:read',
        'sample:export',
    ])]
    #[Assert\AtLeastOneOf([
        new Assert\EqualTo(value: 0, groups: ['validation:sample:create']),
        new Assert\Sequentially([
            new Assert\GreaterThanOrEqual(value: 2000),
            new AppAssert\IsLessThanOrEqualToCurrentYear(),
        ],
            groups: ['validation:sample:create']),
    ],
        groups: ['validation:sample:create']
    )]
    private int $year = 0;

    #[ORM\Column(type: 'smallint')]
    #[Groups([
        'sample:acl:read',
        'sample:export',
    ])]
    #[Assert\NotBlank(groups: [
        'validation:sample:create',
    ])]
    private int $number;

    #[ORM\OneToMany(targetEntity: SampleStratigraphicUnit::class, mappedBy: 'sample')]
    private Collection $sampleStratigraphicUnits;

    #[ORM\OneToMany(targetEntity: AnalysisSampleMicrostratigraphy::class, mappedBy: 'subject')]
    private Collection $analysesMicrostratigraphicUnits;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups([
        'sample:acl:read',
        'sample:export',
    ])]
    private ?string $description;

    public function __construct()
    {
        $this->sampleStratigraphicUnits = new ArrayCollection();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getSite(): ArchaeologicalSite
    {
        return $this->site;
    }

    public function setSite(ArchaeologicalSite $site): Sample
    {
        $this->site = $site;

        return $this;
    }

    public function getType(): Type
    {
        return $this->type;
    }

    public function setType(Type $type): Sample
    {
        $this->type = $type;

        return $this;
    }

    public function getYear(): int
    {
        return $this->year;
    }

    public function setYear(?int $year): Sample
    {
        $this->year = $year ?? 0;

        return $this;
    }

    public function getNumber(): int
    {
        return $this->number;
    }

    public function setNumber(int $number): Sample
    {
        $this->number = $number;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): Sample
    {
        $this->description = $description ?? null;

        return $this;
    }

    public function getSampleStratigraphicUnits(): Collection
    {
        return $this->sampleStratigraphicUnits;
    }

    public function setSampleStratigraphicUnits(Collection $sampleStratigraphicUnits): Sample
    {
        $this->sampleStratigraphicUnits = $sampleStratigraphicUnits;

        return $this;
    }

    #[Groups([
        'sample:acl:read',
        'sample_stratigraphic_unit:samples:acl:read',
    ])]
    public function getCode(): string
    {
        return sprintf(
            '%s.%s.%s.%u',
            $this->getSite()->getCode(),
            $this->type->code,
            substr(0 === $this->year ? '____' : $this->year, -2),
            $this->number
        );
    }
}
