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
use App\Doctrine\Filter\Granted\GrantedContextFilter;
use App\Doctrine\Filter\SearchContextFilter;
use App\Doctrine\Filter\UnaccentedSearchFilter;
use App\Entity\Data\Join\ContextSample;
use App\Entity\Data\Join\ContextStratigraphicUnit;
use App\Entity\Data\Join\ContextZooAnalysis;
use App\Entity\Vocabulary\Context\Type;
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
    name: 'contexts',
)]
#[ORM\UniqueConstraint(columns: ['site_id', 'type_id', 'name'])]
#[ApiResource(
    shortName: 'Context',
    operations: [
        new Get(),
        new GetCollection(
            formats: ['csv' => 'text/csv', 'jsonld' => 'application/ld+json'],
        ),
        new GetCollection(
            uriTemplate: '/sites/{parentId}/contexts',
            formats: ['csv' => 'text/csv', 'jsonld' => 'application/ld+json'],
            uriVariables: [
                'parentId' => new Link(
                    toProperty: 'site',
                    fromClass: Site::class,
                ),
            ]
        ),
        new Post(
            securityPostDenormalize: 'is_granted("create", object)',
            validationContext: ['groups' => ['validation:context:create']],
        ),
        new Patch(
            security: 'is_granted("update", object)',
        ),
        new Delete(
            security: 'is_granted("delete", object)',
        ),
    ],
    routePrefix: 'data',
    normalizationContext: ['groups' => ['context:acl:read']],
    denormalizationContext: ['groups' => ['context:create']],
)]
#[ApiFilter(
    OrderFilter::class,
    properties: ['id', 'site.code', 'name', 'type.group', 'type.value']
)]
#[ApiFilter(
    SearchFilter::class,
    properties: [
        'site' => 'exact',
        'type' => 'exact',
        'contextsStratigraphicUnits.stratigraphicUnit' => 'exact',
        'contextSamples.sample' => 'exact',
        'contextStratigraphicUnits.stratigraphicUnit.year' => 'exact',
        'contextStratigraphicUnits.stratigraphicUnit.number' => 'exact',
    ]
)]
#[ApiFilter(
    RangeFilter::class,
    properties: [
        'contextStratigraphicUnits.stratigraphicUnit.year',
        'contextStratigraphicUnits.stratigraphicUnit.number',
    ]
)]
#[ApiFilter(
    ExistsFilter::class,
    properties: [
        'description',
        'contextStratigraphicUnits.stratigraphicUnit.description',
    ]
)]
#[ApiFilter(
    UnaccentedSearchFilter::class,
    properties: [
        'name',
        'description',
        'contextStratigraphicUnits.stratigraphicUnit.interpretation',
        'contextStratigraphicUnits.stratigraphicUnit.description',
    ]
)]
#[ApiFilter(SearchContextFilter::class)]
#[ApiFilter(GrantedContextFilter::class)]
#[UniqueEntity(
    fields: ['site', 'name'],
    message: 'Duplicate [site, name] combination.',
    groups: ['validation:su:create']
)]
class Context
{
    #[
        ORM\Id,
        ORM\GeneratedValue(strategy: 'SEQUENCE'),
        ORM\Column(type: 'bigint', unique: true)
    ]
    #[SequenceGenerator(sequenceName: 'context_id_seq')]
    #[Groups([
        'context:acl:read',
        'context:export',
        'context_stratigraphic_unit:acl:read',
    ])]
    private int $id;

    #[ORM\ManyToOne(targetEntity: Type::class)]
    #[ORM\JoinColumn(name: 'type_id', nullable: false, onDelete: 'RESTRICT')]
    #[Groups([
        'context:acl:read',
        'context:export',
        'context_stratigraphic_unit:acl:read',
        'context:create',
    ])]
    #[Assert\NotBlank(groups: [
        'validation:context:create',
    ])]
    private Type $type;

    #[ORM\ManyToOne(targetEntity: Site::class)]
    #[ORM\JoinColumn(name: 'site_id', nullable: false, onDelete: 'RESTRICT')]
    #[Groups([
        'context:acl:read',
        'context:export',
        'context_stratigraphic_unit:acl:read',
        'context:create',
    ])]
    #[Assert\NotBlank(groups: [
        'validation:context:create',
    ])]
    private Site $site;

    #[ORM\OneToMany(targetEntity: ContextStratigraphicUnit::class, mappedBy: 'context')]
    private Collection $contextStratigraphicUnits;

    #[ORM\OneToMany(targetEntity: ContextSample::class, mappedBy: 'context')]
    private Collection $contextSamples;

    #[ORM\OneToMany(targetEntity: ContextZooAnalysis::class, mappedBy: 'item')]
    private Collection $contextZooAnalyses;

    #[ORM\Column(type: 'string')]
    #[Groups([
        'context:acl:read',
        'context:export',
        'context_stratigraphic_unit:acl:read',
        'context:create',
    ])]
    #[Assert\NotBlank(groups: [
        'validation:context:create',
    ])]
    private string $name;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups([
        'context:acl:read',
        'context:export',
        'context:create',
    ])]
    private ?string $description;

    public function __construct()
    {
        $this->contextStratigraphicUnits = new ArrayCollection();
        $this->contextSamples = new ArrayCollection();
        $this->contextZooAnalyses = new ArrayCollection();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getType(): Type
    {
        return $this->type;
    }

    public function setType(Type $type): Context
    {
        $this->type = $type;

        return $this;
    }

    public function getSite(): Site
    {
        return $this->site;
    }

    public function setSite(Site $site): Context
    {
        $this->site = $site;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): Context
    {
        $this->name = $name;

        return $this;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): Context
    {
        $this->description = $description;

        return $this;
    }

    public function getContextSamples(): Collection
    {
        return $this->contextSamples;
    }

    public function setContextSamples(Collection $contextSamples): Context
    {
        $this->contextSamples = $contextSamples;

        return $this;
    }

    public function getContextStratigraphicUnits(): Collection
    {
        return $this->contextStratigraphicUnits;
    }

    public function setContextStratigraphicUnits(Collection $contextStratigraphicUnits): Context
    {
        $this->contextStratigraphicUnits = $contextStratigraphicUnits;

        return $this;
    }

    public function getContextZooAnalyses(): Collection
    {
        return $this->contextZooAnalyses;
    }

    public function setContextZooAnalyses(Collection $contextZooAnalyses): Context
    {
        $this->contextZooAnalyses = $contextZooAnalyses;

        return $this;
    }
}
