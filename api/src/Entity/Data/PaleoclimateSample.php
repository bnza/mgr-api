<?php

namespace App\Entity\Data;

use ApiPlatform\Doctrine\Orm\Filter\BooleanFilter;
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
use App\Dto\Output\WfsGetFeatureCollectionExtentMatched;
use App\Dto\Output\WfsGetFeatureCollectionNumberMatched;
use App\Entity\Data\Join\MediaObject\MediaObjectPaleoclimateSample;
use App\Metadata\Attribute\SubResourceFilters\ApiMediaObjectSubresourceFilters;
use App\Metadata\ExportFeatureCollection;
use App\Metadata\GetFeatureCollection;
use App\State\GeoserverFeatureCollectionExtentMatchedProvider;
use App\State\GeoserverFeatureCollectionNumberMatchedProvider;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\SequenceGenerator;
use Doctrine\ORM\Mapping\Table;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[Entity]
#[Table(name: 'paleoclimate_sample')]
#[ORM\UniqueConstraint(columns: ['site_id', 'number'])]
#[ApiResource(
    operations: [
        new Get(
            uriTemplate: '/data/paleoclimate_samples/{id}',
            normalizationContext: ['groups' => ['paleoclimate_sample:acl:read', 'paleoclimate_sampling_sites:acl:read']],
        ),
        new GetCollection(
            uriTemplate: '/data/paleoclimate_samples',
            normalizationContext: ['groups' => ['paleoclimate_sample:acl:read', 'paleoclimate_sampling_sites:acl:read']],
        ),
        new GetCollection(
            uriTemplate: '/data/paleoclimate_sample/{parentId}/samples',
            formats: ['jsonld' => 'application/ld+json', 'csv' => 'text/csv'],
            uriVariables: [
                'parentId' => new Link(
                    toProperty: 'site',
                    fromClass: PaleoclimateSamplingSite::class,
                ),
            ]
        ),
        new Get(
            uriTemplate: '/features/number_matched/paleoclimate_sample',
            defaults: ['typeName' => 'mgr:paleoclimate_samples'],
            normalizationContext: ['groups' => ['wfs_number_matched:read']],
            output: WfsGetFeatureCollectionNumberMatched::class,
            provider: GeoserverFeatureCollectionNumberMatchedProvider::class,
        ),
        new Get(
            uriTemplate: '/features/extent_matched/paleoclimate_sample',
            defaults: ['typeName' => 'mgr:paleoclimate_samples'],
            normalizationContext: ['groups' => ['wfs_extent_matched:read']],
            output: WfsGetFeatureCollectionExtentMatched::class,
            provider: GeoserverFeatureCollectionExtentMatchedProvider::class,
        ),
        new GetFeatureCollection(
            uriTemplate: '/features/paleoclimate_sample.{_format}',
            typeName: 'mgr:paleoclimate_samples',
            propertyNames: ['id', 'code'],
        ),
        new ExportFeatureCollection(
            uriTemplate: '/features/export/paleoclimate_sample',
            typeName: 'mgr:sampling_sites',
        ),
        new Delete(
            uriTemplate: '/data/paleoclimate_samples/{id}',
        ),
        new Patch(
            uriTemplate: '/data/paleoclimate_samples/{id}',
        ),
        new Post(
            uriTemplate: '/data/paleoclimate_samples',
        ),
    ],
    normalizationContext: ['groups' => ['paleoclimate_sample:acl:read']],
    denormalizationContext: ['groups' => ['paleoclimate_sample:create']],
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
        'length',
    ]
)]
#[ApiFilter(
    RangeFilter::class,
    properties: [
        'number',
        'chronologyLower',
        'chronologyUpper',
        'length',
    ]
)]
#[ApiFilter(
    BooleanFilter::class,
    properties: [
        'temperatureRecord',
        'precipitationRecord',
        'stableIsotopes',
        'traceElements',
        'petrographicDescriptions',
        'fluidInclusions',
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
#[ApiMediaObjectSubresourceFilters('mediaObjects.mediaObject')]
class PaleoclimateSample
{
    #[
        ORM\Id,
        ORM\GeneratedValue(strategy: 'SEQUENCE'),
        ORM\Column(type: 'bigint', unique: true)
    ]
    #[SequenceGenerator(sequenceName: 'context_id_seq')]
    #[Groups([
        'paleoclimate_sample:acl:read',
        'paleoclimate_sample:export',
    ])]
    private int $id;

    #[ORM\ManyToOne(targetEntity: PaleoclimateSamplingSite::class, inversedBy: 'samples')]
    #[ORM\JoinColumn(name: 'site_id', nullable: false, onDelete: 'RESTRICT')]
    #[Groups([
        'paleoclimate_sample:acl:read',
        'paleoclimate_sample:create',
        'paleoclimate_sample:export',
        'paleoclimate_sampling_sites:acl:read',
    ])]
    #[Assert\NotBlank]
    #[ApiProperty(required: true)]
    private PaleoclimateSamplingSite $site;

    #[ORM\Column(type: 'integer')]
    #[Groups([
        'paleoclimate_sample:acl:read',
        'paleoclimate_sample:create',
        'paleoclimate_sample:export',
    ])]
    #[Assert\NotBlank]
    #[Assert\Positive]
    #[ApiProperty(required: true)]
    private int $number;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups([
        'paleoclimate_sample:acl:read',
        'paleoclimate_sample:create',
        'paleoclimate_sample:export',
    ])]
    private ?string $description = null;

    #[ORM\Column(type: 'smallint', nullable: true)]
    #[Groups([
        'paleoclimate_sample:acl:read',
        'paleoclimate_sample:create',
        'paleoclimate_sample:export',
    ])]
    #[Assert\GreaterThanOrEqual(value: -32768)]
    #[Assert\LessThanOrEqual(value: 32767)]
    #[Assert\LessThanOrEqual(propertyPath: 'chronologyUpper')]
    private ?int $chronologyLower = null;

    #[ORM\Column(type: 'smallint', nullable: true)]
    #[Groups([
        'paleoclimate_sample:acl:read',
        'paleoclimate_sample:create',
        'paleoclimate_sample:export',
    ])]
    #[Assert\GreaterThanOrEqual(value: -32768)]
    #[Assert\LessThanOrEqual(value: 32767)]
    #[Assert\GreaterThanOrEqual(propertyPath: 'chronologyLower')]
    private ?int $chronologyUpper = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    #[Groups([
        'paleoclimate_sample:acl:read',
        'paleoclimate_sample:create',
        'paleoclimate_sample:export',
    ])]
    private ?int $length = null;

    #[ORM\Column(type: 'boolean')]
    #[Groups([
        'paleoclimate_sample:acl:read',
        'paleoclimate_sample:create',
        'paleoclimate_sample:export',
    ])]
    private bool $temperatureRecord = false;

    #[ORM\Column(type: 'boolean')]
    #[Groups([
        'paleoclimate_sample:acl:read',
        'paleoclimate_sample:create',
        'paleoclimate_sample:export',
    ])]
    private bool $precipitationRecord = false;

    #[ORM\Column(type: 'boolean')]
    #[Groups([
        'paleoclimate_sample:acl:read',
        'paleoclimate_sample:create',
        'paleoclimate_sample:export',
    ])]
    private bool $stableIsotopes = false;

    #[ORM\Column(type: 'boolean')]
    #[Groups([
        'paleoclimate_sample:acl:read',
        'paleoclimate_sample:create',
        'paleoclimate_sample:export',
    ])]
    private bool $traceElements = false;

    #[ORM\Column(type: 'boolean')]
    #[Groups([
        'paleoclimate_sample:acl:read',
        'paleoclimate_sample:create',
        'paleoclimate_sample:export',
    ])]
    private bool $petrographicDescriptions = false;

    #[ORM\Column(type: 'boolean')]
    #[Groups([
        'paleoclimate_sample:acl:read',
        'paleoclimate_sample:create',
        'paleoclimate_sample:export',
    ])]
    private bool $fluidInclusions = false;

    #[ORM\OneToMany(
        targetEntity: MediaObjectPaleoclimateSample::class,
        mappedBy: 'item',
        orphanRemoval: true
    )]
    private Collection $mediaObjects;

    public function __construct()
    {
        $this->mediaObjects = new ArrayCollection();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getSite(): PaleoclimateSamplingSite
    {
        return $this->site;
    }

    public function setSite(PaleoclimateSamplingSite $site): self
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

    public function getLength(): ?int
    {
        return $this->length;
    }

    public function setLength(?int $length): self
    {
        $this->length = $length;

        return $this;
    }

    public function isTemperatureRecord(): bool
    {
        return $this->temperatureRecord;
    }

    public function setTemperatureRecord(bool $temperatureRecord): self
    {
        $this->temperatureRecord = $temperatureRecord;

        return $this;
    }

    public function isPrecipitationRecord(): bool
    {
        return $this->precipitationRecord;
    }

    public function setPrecipitationRecord(bool $precipitationRecord): self
    {
        $this->precipitationRecord = $precipitationRecord;

        return $this;
    }

    public function isStableIsotopes(): bool
    {
        return $this->stableIsotopes;
    }

    public function setStableIsotopes(bool $stableIsotopes): self
    {
        $this->stableIsotopes = $stableIsotopes;

        return $this;
    }

    public function isTraceElements(): bool
    {
        return $this->traceElements;
    }

    public function setTraceElements(bool $traceElements): self
    {
        $this->traceElements = $traceElements;

        return $this;
    }

    public function isPetrographicDescriptions(): bool
    {
        return $this->petrographicDescriptions;
    }

    public function setPetrographicDescriptions(bool $petrographicDescriptions): self
    {
        $this->petrographicDescriptions = $petrographicDescriptions;

        return $this;
    }

    public function isFluidInclusions(): bool
    {
        return $this->fluidInclusions;
    }

    public function setFluidInclusions(bool $fluidInclusions): self
    {
        $this->fluidInclusions = $fluidInclusions;

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

    #[Groups([
        'paleoclimate_sample:acl:read',
        'paleoclimate_sample:export',
        'sediment_core_depth:acl:read',
        'sediment_core_depth:export',
        'sediment_core_depth:stratigraphic_units:acl:read',
    ])]
    #[ApiProperty(required: true)]
    public function getCode(): string
    {
        return sprintf('%s.%u', $this->site->getCode(), $this->number);
    }
}
