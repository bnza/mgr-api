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
use App\Doctrine\Filter\UnaccentedSearchFilter;
use App\Entity\Data\Join\MediaObject\MediaObjectSamplingStratigraphicUnit;
use App\Entity\Data\Join\SedimentCoreDepth;
use App\Repository\SamplingStratigraphicUnitRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\SequenceGenerator;
use Doctrine\ORM\Mapping\Table;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[Entity(repositoryClass: SamplingStratigraphicUnitRepository::class)]
#[Table(name: 'sampling_sus')]
#[ORM\UniqueConstraint(columns: ['site_id', 'number'])]
#[ApiResource(
    operations: [
        new Get(),
        new GetCollection(),
        new GetCollection(
            uriTemplate: '/sampling_sites/{parentId}/stratigraphic_units',
            formats: ['jsonld' => 'application/ld+json', 'csv' => 'text/csv'],
            uriVariables: [
                'parentId' => new Link(
                    toProperty: 'site',
                    fromClass: SamplingSite::class,
                ),
            ]
        ),
        new Delete(),
        new Patch(),
        new Post(),
    ],
    routePrefix: 'data',
    normalizationContext: ['groups' => ['sampling_su:acl:read']],
    denormalizationContext: ['groups' => ['sampling_su:create']],
    order: ['id' => 'DESC'],
)]
#[ApiFilter(
    OrderFilter::class,
    properties: [
        'id',
        'number',
        'chronologyLower',
        'chronologyUpper',
    ])]
#[ApiFilter(
    SearchFilter::class,
    properties: [
        'site' => 'exact',
        'number' => 'exact',
    ]
)]
#[ApiFilter(
    NumericFilter::class,
    properties: [
        'number',
        'chronologyLower',
        'chronologyUpper',
    ]
)]
#[ApiFilter(
    RangeFilter::class,
    properties: [
        'number',
        'chronologyLower',
        'chronologyUpper',
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
        'chronologyLower',
        'chronologyUpper',
        'description',
        'mediaObjects',
    ]
)]
#[UniqueEntity(
    fields: ['site', 'number'],
    message: 'Duplicate [site, number] combination.',
)]
class SamplingStratigraphicUnit
{
    #[
        ORM\Id,
        ORM\GeneratedValue(strategy: 'SEQUENCE'),
        ORM\Column(type: 'bigint', unique: true)
    ]
    #[SequenceGenerator(sequenceName: 'context_id_seq')]
    #[Groups([
        'sampling_su:acl:read',
        'sampling_su:export',
    ])]
    private int $id;

    #[ORM\ManyToOne(targetEntity: SamplingSite::class)]
    #[ORM\JoinColumn(name: 'site_id', nullable: false, onDelete: 'RESTRICT')]
    #[Groups([
        'sampling_su:acl:read',
        'sampling_su:create',
        'sampling_su:export',
        'sediment_core_depth:acl:read',
    ])]
    #[Assert\NotBlank]
    #[ApiProperty(required: true)]
    private SamplingSite $site;

    #[ORM\Column(type: 'integer')]
    #[Groups([
        'sampling_su:acl:read',
        'sampling_su:create',
        'sampling_su:export',
    ])]
    #[Assert\NotBlank]
    #[Assert\Positive]
    #[ApiProperty(required: true)]
    private int $number;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups([
        'sampling_su:acl:read',
        'sampling_su:create',
        'sampling_su:export',
    ])]
    private ?string $description = null;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups([
        'sampling_su:acl:read',
        'sampling_su:create',
        'sampling_su:export',
    ])]
    private ?string $interpretation = null;

    #[ORM\Column(type: 'smallint', nullable: true)]
    #[Groups([
        'sampling_su:acl:read',
        'sampling_su:create',
        'sampling_su:export',
    ])]
    #[Assert\GreaterThanOrEqual(value: -32768)]
    #[Assert\LessThanOrEqual(propertyPath: 'chronologyUpper')]
    private ?int $chronologyLower = null;

    #[ORM\Column(type: 'smallint', nullable: true)]
    #[Groups([
        'sampling_su:acl:read',
        'sampling_su:create',
        'sampling_su:export',
    ])]
    #[Assert\GreaterThanOrEqual(value: -32768)]
    #[Assert\GreaterThanOrEqual(propertyPath: 'chronologyLower')]
    private ?int $chronologyUpper = null;

    #[ORM\OneToMany(
        targetEntity: MediaObjectSamplingStratigraphicUnit::class,
        mappedBy: 'item',
        orphanRemoval: true
    )]
    private Collection $mediaObjects;

    #[ORM\OneToMany(targetEntity: SedimentCoreDepth::class, mappedBy: 'stratigraphicUnit')]
    private Collection $stratigraphicUnitSedimentCores;

    public function __construct()
    {
        $this->mediaObjects = new ArrayCollection();
        $this->stratigraphicUnitSedimentCores = new ArrayCollection();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getSite(): SamplingSite
    {
        return $this->site;
    }

    public function setSite(SamplingSite $site): self
    {
        $this->site = $site;

        return $this;
    }

    public function getNumber(): int
    {
        return $this->number;
    }

    public function setNumber(int $number): self
    {
        $this->number = $number;

        return $this;
    }

    public function getChronologyLower(): ?int
    {
        return $this->chronologyLower;
    }

    public function setChronologyLower(?int $chronologyLower): self
    {
        $this->chronologyLower = $chronologyLower;

        return $this;
    }

    public function getChronologyUpper(): ?int
    {
        return $this->chronologyUpper;
    }

    public function setChronologyUpper(?int $chronologyUpper): self
    {
        $this->chronologyUpper = $chronologyUpper;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getInterpretation(): ?string
    {
        return $this->interpretation;
    }

    public function setInterpretation(?string $interpretation): self
    {
        $this->interpretation = $interpretation;

        return $this;
    }

    public function getMediaObjects(): Collection
    {
        return $this->mediaObjects;
    }

    public function setMediaObjects(Collection $mediaObjects): self
    {
        $this->mediaObjects = $mediaObjects;

        return $this;
    }

    public function getStratigraphicUnitSedimentCores(): Collection
    {
        return $this->stratigraphicUnitSedimentCores;
    }

    public function setStratigraphicUnitSedimentCores(Collection $stratigraphicUnitSedimentCores): self
    {
        $this->stratigraphicUnitSedimentCores = $stratigraphicUnitSedimentCores;

        return $this;
    }

    #[Groups([
        'sampling_su:acl:read',
        'sampling_su:export',
        'sediment_core_depth:acl:read',
        'sediment_core_depth:export',
    ])]
    #[ApiProperty(required: true)]
    public function getCode(): string
    {
        return sprintf('%s.%u', $this->site->getCode(), $this->number);
    }
}
