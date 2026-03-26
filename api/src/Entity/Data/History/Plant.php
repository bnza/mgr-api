<?php

namespace App\Entity\Data\History;

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
use App\Doctrine\Filter\UnaccentedSearchFilter;
use App\Dto\Output\WfsGetFeatureCollectionExtentMatched;
use App\Dto\Output\WfsGetFeatureCollectionNumberMatched;
use App\Entity\Auth\User;
use App\Entity\Vocabulary\History\Language;
use App\Entity\Vocabulary\History\Location;
use App\Entity\Vocabulary\History\Plant as VocabularyPlant;
use App\Metadata\ExportFeatureCollection;
use App\Metadata\GetAggregatedFeatureCollection;
use App\State\GeoserverAggregatedExtentMatchedProvider;
use App\State\GeoserverAggregatedNumberMatchedProvider;
use App\Validator as AppAssert;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\SequenceGenerator;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ORM\Table(
    name: 'history_plants'
)]
#[ApiResource(
    shortName: 'HistoryPlant',
    operations: [
        new Get(
            uriTemplate: '/data/history/plants/{id}',
        ),
        new GetCollection(
            uriTemplate: '/data/history/plants',
            formats: ['jsonld' => 'application/ld+json', 'csv' => 'text/csv'],
        ),
        new GetCollection(
            uriTemplate: '/data/history/locations/{parentId}/plants',
            formats: ['jsonld' => 'application/ld+json', 'csv' => 'text/csv'],
            uriVariables: [
                'parentId' => new Link(
                    toProperty: 'location',
                    fromClass: Location::class,
                ),
            ]
        ),
        new Post(
            uriTemplate: '/data/history/plants',
            formats: ['jsonld' => 'application/ld+json', 'csv' => 'text/csv'],
            securityPostDenormalize: 'is_granted("create", object)',
            validationContext: ['groups' => ['validation:history_plant:create']],
        ),
        new Patch(
            uriTemplate: '/data/history/plants/{id}',
            security: 'is_granted("update", object)',
        ),
        new Delete(
            uriTemplate: '/data/history/plants/{id}',
            security: 'is_granted("delete", object)',
        ),
        new GetAggregatedFeatureCollection(
            uriTemplate: '/features/history/plants.{_format}',
            typeName: 'mgr:history_locations',
            parentAccessor: 'location',
            entityTypeName: 'mgr:history_plants',
            propertyNames: ['id', 'value'],
        ),
        new Get(
            uriTemplate: '/features/number_matched/history/plants',
            defaults: ['typeName' => 'mgr:history_locations', 'parentAccessor' => 'location'],
            normalizationContext: ['groups' => ['wfs_number_matched:read']],
            output: WfsGetFeatureCollectionNumberMatched::class,
            provider: GeoserverAggregatedNumberMatchedProvider::class,
        ),
        new Get(
            uriTemplate: '/features/extent_matched/history/plants',
            defaults: ['typeName' => 'mgr:history_locations', 'parentAccessor' => 'location'],
            normalizationContext: ['groups' => ['wfs_extent_matched:read']],
            output: WfsGetFeatureCollectionExtentMatched::class,
            provider: GeoserverAggregatedExtentMatchedProvider::class,
        ),
        new ExportFeatureCollection(
            uriTemplate: '/features/export/history/plants',
            typeName: 'mgr:history_plants',
        ),
    ],
    normalizationContext: ['groups' => ['history_plant:acl:read']],
    denormalizationContext: ['groups' => ['history_plant:create']],
    order: ['id' => 'DESC'],
)]
#[ApiFilter(
    OrderFilter::class,
    properties: [
        'chronologyLower',
        'chronologyUpper',
        'createdBy.email',
        'language.value',
        'location.region.value',
        'location.value',
        'plant.value',
        'reference',
    ])]
#[ApiFilter(
    SearchFilter::class,
    properties: [
        'plant' => 'exact',
        'plant.taxonomy' => 'exact',
        'plant.taxonomy.family' => 'exact',
        'plant.taxonomy.class' => 'exact',
        'plant.taxonomy.vernacularName' => 'ipartial',
        'language' => 'exact',
        'location' => 'exact',
        'chronologyLower' => 'exact',
        'chronologyUpper' => 'exact',
        'createdBy.email' => 'exact']
)]
#[ApiFilter(
    RangeFilter::class,
    properties: [
        'chronologyLower',
        'chronologyUpper']
)]
#[ApiFilter(
    ExistsFilter::class,
    properties: [
        'plant.taxonomy.family',
        'notes',
    ])]
#[ApiFilter(
    UnaccentedSearchFilter::class,
    properties: [
        'location.region.value',
        'notes',
        'reference',
    ]
)]
class Plant
{
    #[ORM\Id,
        ORM\GeneratedValue(strategy: 'SEQUENCE'),
        ORM\Column(type: 'bigint', unique: true)]
    #[SequenceGenerator(sequenceName: 'history_cit_item_id_seq')]
    #[Groups([
        'history_plant:acl:read',
        'history_plant:export',
    ])]
    private int $id;

    #[ORM\ManyToOne(targetEntity: Language::class)]
    #[ORM\JoinColumn(name: 'age_id', referencedColumnName: 'id', nullable: false, onDelete: 'RESTRICT')]
    #[Groups([
        'history_plant:acl:read',
        'history_plant:export',
        'history_plant:create',
    ])]
    #[ApiProperty(required: true)]
    private Language $language;

    #[ORM\ManyToOne(targetEntity: VocabularyPlant::class)]
    #[ORM\JoinColumn(name: 'plant_id', referencedColumnName: 'id', nullable: false, onDelete: 'RESTRICT')]
    #[Groups([
        'history_plant:acl:read',
        'history_plant:export',
        'history_plant:create',
    ])]
    #[Assert\NotBlank(groups: [
        'validation:history_plant:create',
    ])]
    private VocabularyPlant $plant;

    #[ORM\ManyToOne(targetEntity: Location::class, inversedBy: 'plants')]
    #[ORM\JoinColumn(name: 'location_id', referencedColumnName: 'id', nullable: false, onDelete: 'RESTRICT')]
    #[Groups([
        'history_plant:acl:read',
        'history_plant:export',
        'history_plant:create',
    ])]
    #[Assert\NotBlank(groups: [
        'validation:history_plant:create',
    ])]
    private Location $location;

    #[ORM\Column(type: 'smallint')]
    #[Groups([
        'history_plant:acl:read',
        'history_plant:export',
        'history_plant:create',
    ])]
    #[Assert\NotBlank(groups: [
        'validation:history_plant:create',
    ])]
    #[Assert\GreaterThanOrEqual(value: -32768, groups: ['validation:history_plant:create'])]
    #[AppAssert\IsLessThanOrEqualToCurrentYear(groups: ['validation:history_plant:create'])]
    #[Assert\LessThanOrEqual(propertyPath: 'chronologyUpper', groups: ['validation:history_plant:create'])]
    private int $chronologyLower;

    #[ORM\Column(type: 'smallint')]
    #[Groups([
        'history_plant:acl:read',
        'history_plant:export',
        'history_plant:create',
    ])]
    #[Assert\NotBlank(groups: [
        'validation:history_plant:create',
    ])]
    #[Assert\GreaterThanOrEqual(value: -32768, groups: ['validation:history_plant:create'])]
    #[AppAssert\IsLessThanOrEqualToCurrentYear(groups: ['validation:history_plant:create'])]
    #[Assert\GreaterThanOrEqual(propertyPath: 'chronologyLower', groups: ['validation:history_plant:create'])]
    private int $chronologyUpper;

    #[ORM\Column(type: 'string')]
    #[Groups([
        'history_plant:acl:read',
        'history_plant:export',
        'history_plant:create',
    ])]
    #[Assert\NotBlank(groups: [
        'validation:history_plant:create',
    ])]
    private string $reference;

    #[ORM\Column(type: 'string', nullable: true)]
    #[Groups([
        'history_plant:acl:read',
        'history_plant:export',
        'history_plant:create',
    ])]
    private ?string $notes;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'created_by_id', referencedColumnName: 'id', onDelete: 'RESTRICT')]
    #[Groups([
        'history_plant:acl:read',
    ])]
    private User $createdBy;

    public function getId(): int
    {
        return $this->id;
    }

    public function getLanguage(): ?Language
    {
        return $this->language;
    }

    public function setLanguage(?Language $language): Plant
    {
        $this->language = $language;

        return $this;
    }

    public function getPlant(): VocabularyPlant
    {
        return $this->plant;
    }

    public function setPlant(VocabularyPlant $plant): Plant
    {
        $this->plant = $plant;

        return $this;
    }

    public function getLocation(): Location
    {
        return $this->location;
    }

    public function setLocation(Location $location): Plant
    {
        $this->location = $location;

        return $this;
    }

    public function getChronologyLower(): int
    {
        return $this->chronologyLower;
    }

    public function setChronologyLower(int $chronologyLower): Plant
    {
        $this->chronologyLower = $chronologyLower;

        return $this;
    }

    public function getChronologyUpper(): int
    {
        return $this->chronologyUpper;
    }

    public function setChronologyUpper(int $chronologyUpper): Plant
    {
        $this->chronologyUpper = $chronologyUpper;

        return $this;
    }

    public function getReference(): string
    {
        return $this->reference;
    }

    public function setReference(string $reference): Plant
    {
        $this->reference = $reference;

        return $this;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): Plant
    {
        $this->notes = $notes ?? null;

        return $this;
    }

    public function getCreatedBy(): User
    {
        return $this->createdBy;
    }

    public function setCreatedBy(User $createdBy): Plant
    {
        $this->createdBy = $createdBy;

        return $this;
    }
}
