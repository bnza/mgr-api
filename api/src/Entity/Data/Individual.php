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
use App\Doctrine\Filter\Granted\GrantedParentStratigraphicUnitFilter;
use App\Doctrine\Filter\SearchIndividualFilter;
use App\Doctrine\Filter\UnaccentedSearchFilter;
use App\Entity\Data\Join\Analysis\AnalysisIndividual;
use App\Entity\Vocabulary\Individual\Age;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ORM\Table(
    name: 'individuals',
)]
#[ORM\UniqueConstraint(columns: ['identifier'])]
#[ApiResource(
    operations: [
        new Get(),
        new GetCollection(
            formats: ['jsonld' => 'application/ld+json', 'csv' => 'text/csv'],
        ),
        new GetCollection(
            uriTemplate: '/stratigraphic_units/{parentId}/individuals',
            formats: ['jsonld' => 'application/ld+json', 'csv' => 'text/csv'],
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
            validationContext: ['groups' => ['validation:individual:create']],
        ),
    ],
    routePrefix: 'data',
    normalizationContext: ['groups' => ['individual:acl:read']],
    denormalizationContext: ['groups' => ['individual:create']],
    order: ['id' => 'DESC'],
)]
#[ApiFilter(OrderFilter::class, properties: [
    'id',
    'stratigraphicUnit.site.code',
    'identifier',
    'sex',
    'age.id',
])]
#[ApiFilter(
    SearchFilter::class,
    properties: [
        'age' => 'exact',
        'sex' => 'exact',
        'identifier' => 'ipartial',
        'stratigraphicUnit.site' => 'exact',
        'stratigraphicUnit' => 'exact',
        'stratigraphicUnit.chronologyLower' => 'exact',
        'stratigraphicUnit.chronologyUpper' => 'exact',
    ])
]
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
        'age',
        'sex',
        'notes',
        'stratigraphicUnit.year',
        'stratigraphicUnit.chronologyLower',
        'stratigraphicUnit.chronologyUpper',
    ]
)]
#[ApiFilter(UnaccentedSearchFilter::class, properties: [
    'notes',
    'stratigraphicUnit.description',
    'stratigraphicUnit.interpretation',
])]
#[ApiFilter(
    SearchIndividualFilter::class,
)]
#[ApiFilter(
    GrantedParentStratigraphicUnitFilter::class
)]
#[UniqueEntity(fields: ['identifier'], groups: ['validation:individual:create'])]
class Individual
{
    #[
        ORM\Id,
        ORM\GeneratedValue(strategy: 'SEQUENCE'),
        ORM\Column(type: 'bigint', unique: true)
    ]
    #[Groups([
        'individual:acl:read',
        'individual:export',
    ])]
    private int $id;

    #[ORM\ManyToOne(targetEntity: StratigraphicUnit::class, inversedBy: 'individuals')]
    #[ORM\JoinColumn(name: 'stratigraphic_unit_id', referencedColumnName: 'id', nullable: false, onDelete: 'RESTRICT')]
    #[Groups([
        'individual:acl:read',
        'individual:create',
        'individual:export',
    ])]
    #[Assert\NotBlank(groups: [
        'validation:individual:create',
    ])]
    private StratigraphicUnit $stratigraphicUnit;

    #[ORM\Column(type: 'string')]
    #[Assert\NotBlank(groups: [
        'validation:individual:create',
    ])]
    #[Groups([
        'individual:acl:read',
        'individual:create',
        'individual:export',
    ])]
    private string $identifier;

    #[ORM\ManyToOne(targetEntity: Age::class)]
    #[ORM\JoinColumn(name: 'age_id', referencedColumnName: 'id', nullable: true, onDelete: 'RESTRICT')]
    #[Groups([
        'individual:acl:read',
        'individual:create',
        'individual:export',
    ])]
    private ?Age $age = null;

    #[ORM\Column(type: 'string', nullable: true, options: ['fixed' => true, 'length' => 1, 'comment' => 'F = female, M = male, ? = indeterminate'])]
    #[Groups([
        'individual:acl:read',
        'individual:create',
        'individual:export',
    ])]
    #[Assert\Choice(['F', 'M', '?'], groups: [
        'validation:individual:create',
    ])]
    private ?string $sex = null;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups([
        'individual:acl:read',
        'individual:create',
        'individual:export',
    ])]
    private ?string $notes;

    /** @var Collection<AnalysisIndividual> */
    #[ORM\OneToMany(
        targetEntity: AnalysisIndividual::class,
        mappedBy: 'subject',
        cascade: ['persist', 'remove'],
        orphanRemoval: true,
    )]
    private Collection $analyses;

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

    public function setStratigraphicUnit(StratigraphicUnit $stratigraphicUnit): Individual
    {
        $this->stratigraphicUnit = $stratigraphicUnit;

        return $this;
    }

    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    public function setIdentifier(string $identifier): Individual
    {
        $this->identifier = $identifier;

        return $this;
    }

    public function getAge(): ?Age
    {
        return $this->age;
    }

    public function setAge(?Age $age): Individual
    {
        $this->age = $age;

        return $this;
    }

    public function getSex(): ?string
    {
        return $this->sex;
    }

    public function setSex(?string $sex): Individual
    {
        $this->sex = $sex ?? null;

        return $this;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): Individual
    {
        $this->notes = $notes ?? null;

        return $this;
    }

    public function getAnalyses(): Collection
    {
        return $this->analyses;
    }

    public function setAnalyses(Collection $analyses): Individual
    {
        $this->analyses = $analyses;

        return $this;
    }

    //    #[Groups([
    //        'individual:acl:read',
    //        'individual:export',
    //    ])]
    //    public function getCode(): string
    //    {
    //        return sprintf(
    //            '%s.%s',
    //            $this->getStratigraphicUnit()->getCode(),
    //            $this->getIdentifier(),
    //        );
    //    }
}
