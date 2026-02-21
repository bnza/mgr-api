<?php

namespace App\Entity\Data;

use ApiPlatform\Doctrine\Orm\Filter\OrderFilter;
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
use App\Doctrine\Filter\Granted\GrantedParentSiteFilter;
use App\Doctrine\Filter\SearchSedimentCoreFilter;
use App\Doctrine\Filter\UnaccentedSearchFilter;
use App\Entity\Data\Join\SedimentCoreDepth;
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
    name: 'sediment_cores',
)]
#[ORM\UniqueConstraint(columns: ['site_id', 'year', 'number'])]
#[ORM\HasLifecycleCallbacks]
#[ApiResource(
    operations: [
        new Get(),
        new GetCollection(
            formats: ['jsonld' => 'application/ld+json', 'csv' => 'text/csv'],
        ),
        new GetCollection(
            uriTemplate: '/sites/{parentId}/sediment_cores',
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
            validationContext: ['groups' => ['validation:sediment_core:create']],
        ),
        new Patch(
            security: 'is_granted("update", object)',
        ),
        new Delete(
            security: 'is_granted("delete", object)',
        ),
    ],
    routePrefix: 'data',
    normalizationContext: ['groups' => ['sediment_core:acl:read']],
    order: ['id' => 'DESC'],
)]
#[ApiFilter(
    OrderFilter::class,
    properties: ['id', 'site.code', 'year', 'number']
)]
#[ApiFilter(
    SearchFilter::class,
    properties: [
        'site' => 'exact',
        'sedimentCoresStratigraphicUnits.stratigraphicUnit' => 'exact',
    ]
)]
#[ApiFilter(
    UnaccentedSearchFilter::class,
    properties: [
        'description',
    ]
)]
#[ApiFilter(SearchSedimentCoreFilter::class)]
#[ApiFilter(GrantedParentSiteFilter::class)]
#[UniqueEntity(
    fields: ['site', 'year', 'number'],
    message: 'Duplicate [site, year, number] combination.',
    groups: ['validation:sediment_core:create']
)]
class SedimentCore
{
    #[
        ORM\Id,
        ORM\GeneratedValue(strategy: 'SEQUENCE'),
        ORM\Column(type: 'bigint', unique: true)
    ]
    #[SequenceGenerator(sequenceName: 'context_id_seq')]
    #[Groups([
        'sediment_core:acl:read',
        'sediment_core:export',
    ])]
    #[ApiProperty(required: true)]
    private int $id;

    #[ORM\ManyToOne(targetEntity: ArchaeologicalSite::class)]
    #[ORM\JoinColumn(name: 'site_id', nullable: false, onDelete: 'RESTRICT')]
    #[Groups([
        'sediment_core:acl:read',
        'sediment_core:export',
    ])]
    #[Assert\NotBlank(groups: [
        'validation:sediment_core:create',
    ])]
    #[ApiProperty(required: true)]
    private ArchaeologicalSite $site;

    #[ORM\Column(type: 'smallint')]
    #[Groups([
        'sediment_core:acl:read',
        'sediment_core:export',
    ])]
    #[Assert\NotBlank(groups: [
        'validation:sediment_core:create',
    ])]
    #[Assert\Sequentially(
        [
            new Assert\GreaterThanOrEqual(value: 2000),
            new AppAssert\IsLessThanOrEqualToCurrentYear(),
        ],
        groups: ['validation:sediment_core:create']
    )]
    #[ApiProperty(required: true)]
    private int $year;

    #[ORM\Column(type: 'smallint')]
    #[Groups([
        'sediment_core:acl:read',
        'sediment_core:export',
    ])]
    #[Assert\NotBlank(groups: [
        'validation:sediment_core:create',
    ])]
    #[ApiProperty(required: true)]
    private int $number;

    #[ORM\OneToMany(targetEntity: SedimentCoreDepth::class, mappedBy: 'sedimentCore')]
    private Collection $sedimentCoresStratigraphicUnits;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups([
        'sediment_core:acl:read',
        'sediment_core:export',
    ])]
    private ?string $description;

    public function __construct()
    {
        $this->sedimentCoresStratigraphicUnits = new ArrayCollection();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getSite(): ArchaeologicalSite
    {
        return $this->site;
    }

    public function setSite(ArchaeologicalSite $site): SedimentCore
    {
        $this->site = $site;

        return $this;
    }

    public function getYear(): int
    {
        return $this->year;
    }

    public function setYear(?int $year): SedimentCore
    {
        $this->year = $year ?? 0;

        return $this;
    }

    public function getNumber(): int
    {
        return $this->number;
    }

    public function setNumber(int $number): SedimentCore
    {
        $this->number = $number;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): SedimentCore
    {
        $this->description = $description ?? null;

        return $this;
    }

    public function getSedimentCoresStratigraphicUnits(): Collection
    {
        return $this->sedimentCoresStratigraphicUnits;
    }

    public function setSedimentCoresStratigraphicUnits(Collection $sedimentCoresStratigraphicUnits): SedimentCore
    {
        $this->sedimentCoresStratigraphicUnits = $sedimentCoresStratigraphicUnits;

        return $this;
    }

    #[Groups([
        'sediment_core:acl:read',
        'sample_stratigraphic_unit:sediment_cores:acl:read',
    ])]
    public function getCode(): string
    {
        return sprintf(
            '%s.SC.%s.%u',
            $this->getSite()->getCode(),
            substr($this->year, -2),
            $this->number
        );
    }
}
