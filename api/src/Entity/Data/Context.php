<?php

namespace App\Entity\Data;

use ApiPlatform\Doctrine\Orm\Filter\ExistsFilter;
use ApiPlatform\Doctrine\Orm\Filter\OrderFilter;
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
use App\Doctrine\Filter\SearchContextFilter;
use App\Doctrine\Filter\UnaccentedSearchFilter;
use App\Entity\Data\Join\Analysis\AnalysisContextBotany;
use App\Entity\Data\Join\Analysis\AnalysisContextZoo;
use App\Entity\Data\Join\ContextStratigraphicUnit;
use App\Metadata\Attribute\SubResourceFilters\ApiStratigraphicUnitSubresourceFilters;
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
#[ORM\UniqueConstraint(columns: ['site_id', 'name'])]
#[ApiResource(
    shortName: 'Context',
    operations: [
        new Get(),
        new GetCollection(
            formats: ['csv' => 'text/csv', 'jsonld' => 'application/ld+json'],
        ),
        new GetCollection(
            uriTemplate: '/archaeological_sites/{parentId}/contexts',
            formats: ['csv' => 'text/csv', 'jsonld' => 'application/ld+json'],
            uriVariables: [
                'parentId' => new Link(
                    toProperty: 'site',
                    fromClass: ArchaeologicalSite::class,
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
    order: ['id' => 'DESC'],
)]
#[ApiFilter(
    OrderFilter::class,
    properties: ['id', 'site.code', 'name', 'type']
)]
#[ApiFilter(
    SearchFilter::class,
    properties: [
        'site' => 'exact',
        'type' => 'exact',
        'contextsStratigraphicUnits.stratigraphicUnit' => 'exact',
    ]
)]
#[ApiFilter(
    ExistsFilter::class,
    properties: [
        'description',
    ]
)]
#[ApiFilter(
    UnaccentedSearchFilter::class,
    properties: [
        'name',
        'description',
    ]
)]
#[ApiFilter(
    ExistsFilter::class,
    properties: [
        'description',
    ]
)]
#[ApiFilter(SearchContextFilter::class)]
#[ApiFilter(GrantedParentSiteFilter::class)]
#[ApiStratigraphicUnitSubresourceFilters('contextStratigraphicUnits.stratigraphicUnit')]
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
    private string $type;

    #[ORM\ManyToOne(targetEntity: ArchaeologicalSite::class)]
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
    private ArchaeologicalSite $site;

    #[ORM\OneToMany(targetEntity: ContextStratigraphicUnit::class, mappedBy: 'context')]
    private Collection $contextStratigraphicUnits;

    #[ORM\OneToMany(targetEntity: AnalysisContextBotany::class, mappedBy: 'subject')]
    private Collection $botanyAnalyses;

    #[ORM\OneToMany(targetEntity: AnalysisContextZoo::class, mappedBy: 'subject')]
    private Collection $zooAnalyses;

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
        $this->botanyAnalyses = new ArrayCollection();
        $this->zooAnalyses = new ArrayCollection();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): Context
    {
        $this->type = $type;

        return $this;
    }

    public function getSite(): ArchaeologicalSite
    {
        return $this->site;
    }

    public function setSite(ArchaeologicalSite $site): Context
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

    public function getContextStratigraphicUnits(): Collection
    {
        return $this->contextStratigraphicUnits;
    }

    public function setContextStratigraphicUnits(Collection $contextStratigraphicUnits): Context
    {
        $this->contextStratigraphicUnits = $contextStratigraphicUnits;

        return $this;
    }

    public function getBotanyAnalyses(): Collection
    {
        return $this->botanyAnalyses;
    }

    public function setBotanyAnalyses(Collection $botanyAnalyses): Context
    {
        $this->botanyAnalyses = $botanyAnalyses;

        return $this;
    }

    public function getZooAnalyses(): Collection
    {
        return $this->zooAnalyses;
    }

    public function setZooAnalyses(Collection $zooAnalyses): Context
    {
        $this->zooAnalyses = $zooAnalyses;

        return $this;
    }
}
