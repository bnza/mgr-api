<?php

declare(strict_types=1);

namespace App\Entity\Vocabulary\History;

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
use ApiPlatform\Metadata\Post;
use App\Doctrine\Filter\SearchPropertyAliasFilter;
use App\Doctrine\Filter\UnaccentedSearchFilter;
use App\Dto\Output\WfsGetFeatureCollectionExtentMatched;
use App\Dto\Output\WfsGetFeatureCollectionNumberMatched;
use App\Entity\Data\History\Animal;
use App\Entity\Data\History\Plant;
use App\Metadata\GetFeatureCollection;
use App\State\GeoserverFeatureCollectionExtentMatchedProvider;
use App\State\GeoserverFeatureCollectionNumberMatchedProvider;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use LongitudeOne\Spatial\PHP\Types\Geography\Point;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ORM\Table(
    name: 'history_locations',
    schema: 'vocabulary'
)]
#[ORM\UniqueConstraint(fields: ['value'])]
#[ApiResource(
    shortName: 'VocHistoryLocation',
    operations: [
        new Get(
            uriTemplate: '/vocabulary/history/locations/{id}',
            normalizationContext: ['groups' => ['voc_history_location:read']],
        ),
        new Get(
            uriTemplate: '/features/number_matched/history/locations',
            defaults: ['typeName' => 'mgr:history_locations'],
            normalizationContext: ['groups' => ['wfs_number_matched:read']],
            output: WfsGetFeatureCollectionNumberMatched::class,
            provider: GeoserverFeatureCollectionNumberMatchedProvider::class,
        ),
        new Get(
            uriTemplate: '/features/extent_matched/history/locations',
            defaults: ['typeName' => 'mgr:history_locations'],
            normalizationContext: ['groups' => ['wfs_extent_matched:read']],
            output: WfsGetFeatureCollectionExtentMatched::class,
            provider: GeoserverFeatureCollectionExtentMatchedProvider::class,
        ),
        new GetCollection(
            uriTemplate: '/vocabulary/history/locations',
            order: ['value' => 'ASC'],
            normalizationContext: ['groups' => ['voc_history_location:read']],
        ),
        new GetCollection(
            uriTemplate: '/data/vocabulary/history/locations',
            paginationEnabled: true,
            order: ['id' => 'DESC'],
            normalizationContext: ['groups' => ['voc_history_location:acl:read']],
        ),
        new GetFeatureCollection(
            uriTemplate: '/features/history/locations.{_format}',
            typeName: 'mgr:history_locations',
        ),
        new Post(
            uriTemplate: '/vocabulary/history/locations',
            denormalizationContext: ['groups' => ['voc_history_location:create']],
            securityPostDenormalize: 'is_granted("create", object)',
            validationContext: ['groups' => ['validation:voc_history_location:create']],
        ),
        new Delete(
            uriTemplate: '/vocabulary/history/locations/{id}',
            security: 'is_granted("delete", object)'
        ),
    ],
    paginationEnabled: false
)]
#[ApiFilter(OrderFilter::class, properties: ['id', 'value', 'point.y', 'point.x'])]
#[ApiFilter(
    SearchFilter::class,
    properties: [
        'plants.plant' => 'exact',
        'plants.plant.taxonomy' => 'exact',
        'plants.plant.taxonomy.family' => 'exact',
        'plants.plant.taxonomy.class' => 'exact',
        'plants.plant.taxonomy.vernacularName' => 'ipartial',
        'plants.chronologyLower' => 'exact',
        'plants.chronologyUpper' => 'exact',
        'plants.createdBy.email' => 'exact']
)]
#[ApiFilter(
    RangeFilter::class,
    properties: [
        'plants.chronologyLower',
        'plants.chronologyUpper']
)]
#[ApiFilter(
    ExistsFilter::class,
    properties: [
        'plants.plant.taxonomy.family',
        'plants.notes',
    ])]
#[ApiFilter(
    UnaccentedSearchFilter::class,
    properties: [
        'value',
        'plants.reference',
        'plants.notes',
    ]
)]
#[ApiFilter(
    SearchPropertyAliasFilter::class,
    properties: [
        'search' => 'value',
    ]
)]
#[UniqueEntity(fields: ['value'])]
class Location
{
    #[
        ORM\Id,
        ORM\GeneratedValue(strategy: 'SEQUENCE'),
        ORM\Column(type: 'bigint', unique: true)
    ]
    #[Groups([
        'voc_history_location:read',
        'voc_history_location:acl:read',
        'feature_collection:json:read',
    ])]
    private int $id;

    #[ORM\Column(type: 'string')]
    #[Groups([
        'voc_history_location:read',
        'voc_history_location:acl:read',
        'history_plant:acl:read',
        'history_plant:export',
        'history_animal:export',
        'history_animal:acl:read',
        'voc_history_location:create',
    ])]
    #[Assert\NotBlank(groups: [
        'validation:voc_history_location:create',
    ])]
    #[ApiProperty(required: true)]
    private string $value;

    #[ORM\Column(name: 'the_geom', type: 'geography_point', options: ['srid' => 4326])]
    #[Assert\NotBlank(groups: [
        'validation:voc_history_location:create',
    ])]
    private Point $point;

    #[ORM\OneToMany(targetEntity: Animal::class, mappedBy: 'location')]
    private Collection $animals;

    #[ORM\OneToMany(targetEntity: Plant::class, mappedBy: 'location')]
    private Collection $plants;

    public function __construct()
    {
        $this->animals = new ArrayCollection();
        $this->plants = new ArrayCollection();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function setValue(string $value): Location
    {
        $this->value = $value;

        return $this;
    }

    public function getPoint(): Point
    {
        return $this->point;
    }

    public function setPoint(Point $point): Location
    {
        $this->point = $point;

        return $this;
    }

    #[Groups([
        'voc_history_location:acl:read',
        'history_animal:export',
        'history_plant:export',
        'voc_history_location:read',
    ])]
    public function getN(): float
    {
        return $this->point->getLatitude();
    }

    #[Groups([
        'voc_history_location:create',
    ])]
    public function setN(float $n): Location
    {
        if (!isset($this->point)) {
            $this->point = new Point(0, 0);
        }
        $this->point->setLatitude($n);

        return $this;
    }

    #[Groups([
        'voc_history_location:acl:read',
        'history_animal:export',
        'history_plant:export',
        'voc_history_location:read',
    ])]
    public function getE(): float
    {
        return $this->point->getLongitude();
    }

    #[Groups([
        'voc_history_location:create',
    ])]
    public function setE(float $e): Location
    {
        if (!isset($this->point)) {
            $this->point = new Point(0, 0);
        }
        $this->point->setLongitude($e);

        return $this;
    }
}
