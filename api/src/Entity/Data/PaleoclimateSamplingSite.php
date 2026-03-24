<?php

declare(strict_types=1);

namespace App\Entity\Data;

use ApiPlatform\Doctrine\Orm\Filter\ExistsFilter;
use ApiPlatform\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use App\Doctrine\Filter\UnaccentedSearchFilter;
use App\Dto\Output\WfsGetFeatureCollectionExtentMatched;
use App\Dto\Output\WfsGetFeatureCollectionNumberMatched;
use App\Entity\Data\Join\MediaObject\MediaObjectPaleoclimateSamplingSite;
use App\Entity\Vocabulary\Region;
use App\Metadata\Attribute\SubResourceFilters\ApiMediaObjectSubresourceFilters;
use App\Metadata\ExportFeatureCollection;
use App\Metadata\GetFeatureCollection;
use App\Repository\PaleoclimateSamplingSiteRepository;
use App\State\GeoserverFeatureCollectionExtentMatchedProvider;
use App\State\GeoserverFeatureCollectionNumberMatchedProvider;
use App\Validator as AppAssert;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\SequenceGenerator;
use Doctrine\ORM\Mapping\Table;
use LongitudeOne\Spatial\PHP\Types\Geography\Point;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[Entity(repositoryClass: PaleoclimateSamplingSiteRepository::class)]
#[Table(name: 'paleoclimate_sampling_sites')]
#[ApiResource(
    operations: [
        new GetCollection(
            uriTemplate: '/data/paleoclimate_sampling_sites',
            formats: ['jsonld' => 'application/ld+json', 'csv' => 'text/csv'],
        ),
        new Get(
            uriTemplate: '/data/paleoclimate_sampling_sites/{id}',
        ),
        new Get(
            uriTemplate: '/features/number_matched/paleoclimate_sampling_sites',
            defaults: ['typeName' => 'mgr:paleoclimate_sampling_sites'],
            normalizationContext: ['groups' => ['wfs_number_matched:read']],
            output: WfsGetFeatureCollectionNumberMatched::class,
            provider: GeoserverFeatureCollectionNumberMatchedProvider::class,
        ),
        new Get(
            uriTemplate: '/features/extent_matched/paleoclimate_sampling_sites',
            defaults: ['typeName' => 'mgr:paleoclimate_sampling_sites'],
            normalizationContext: ['groups' => ['wfs_extent_matched:read']],
            output: WfsGetFeatureCollectionExtentMatched::class,
            provider: GeoserverFeatureCollectionExtentMatchedProvider::class,
        ),
        new GetFeatureCollection(
            uriTemplate: '/features/paleoclimate_sampling_sites.{_format}',
            typeName: 'mgr:paleoclimate_sampling_sites',
            propertyNames: ['id', 'code', 'name'],
        ),
        new ExportFeatureCollection(
            uriTemplate: '/features/export/paleoclimate_sampling_sites',
            typeName: 'mgr:paleoclimate_sampling_sites',
        ),
        new Delete(
            uriTemplate: '/data/paleoclimate_sampling_sites/{id}',
            security: 'is_granted("delete", object)',
            validationContext: ['groups' => ['validation:paleoclimate_sampling_sites:delete']],
            validate: true
        ),
        new Patch(
            uriTemplate: '/data/paleoclimate_sampling_sites/{id}',
            security: 'is_granted("update", object)',
            validationContext: ['groups' => ['validation:paleoclimate_sampling_sites:create']],
        ),
        new Post(
            uriTemplate: '/data/paleoclimate_sampling_sites',
            securityPostDenormalize: 'is_granted("create", object)',
            validationContext: ['groups' => ['validation:paleoclimate_sampling_sites:create']],
        ),
    ],
    normalizationContext: ['groups' => ['paleoclimate_sampling_sites:acl:read']],
    denormalizationContext: ['groups' => ['paleoclimate_sampling_sites:create']],
    order: ['id' => 'DESC'],
)]
#[ApiFilter(
    OrderFilter::class,
    properties: ['id', 'code', 'name', 'region.value']
)]
#[ApiFilter(
    SearchFilter::class,
    properties: [
        'code' => 'exact',
        'region' => 'exact',
    ]
)]
#[ApiFilter(
    UnaccentedSearchFilter::class,
    properties: [
        'name',
        'description',
        'region.value',
    ]
)]
#[ApiFilter(
    ExistsFilter::class,
    properties: [
        'description',
    ]
)]
#[UniqueEntity(
    fields: ['code'],
    message: 'Duplicate sampling site code.',
    groups: ['validation:paleoclimate_sampling_sites:create']
)]
#[UniqueEntity(
    fields: ['name'],
    message: 'Duplicate sampling site name.',
    groups: ['validation:paleoclimate_sampling_sites:create']
)]
#[ApiMediaObjectSubresourceFilters('mediaObjects.mediaObject')]
#[AppAssert\NotReferenced(self::class, message: 'Cannot delete the sampling site because it is referenced by: {{ classes }}.', groups: ['validation:paleoclimate_sampling_sites:delete'])]
class PaleoclimateSamplingSite
{
    #[
        ORM\Id,
        ORM\GeneratedValue(strategy: 'SEQUENCE'),
        ORM\Column(type: 'bigint', unique: true)
    ]
    #[SequenceGenerator(sequenceName: 'context_id_seq')]
    #[Groups([
        'paleoclimate_sampling_sites:acl:read',
        'paleoclimate_sampling_sites:export',
    ])]
    private int $id;

    #[ORM\Column(type: 'string', unique: true)]
    #[Groups([
        'sampling_su:acl:read',
        'sediment_core:acl:read',
        'paleoclimate_sampling_sites:acl:read',
        'paleoclimate_sampling_sites:create',
        'paleoclimate_sampling_sites:export',
    ])]
    #[Assert\NotBlank(groups: ['validation:paleoclimate_sampling_sites:create'])]
    private string $code;

    #[ORM\Column(type: 'string', unique: true)]
    #[Groups([
        'sampling_su:acl:read',
        'sediment_core:acl:read',
        'paleoclimate_sampling_sites:acl:read',
        'paleoclimate_sampling_sites:create',
        'paleoclimate_sampling_sites:export',
    ])]
    #[Assert\NotBlank(groups: ['validation:paleoclimate_sampling_sites:create'])]
    private string $name;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups([
        'paleoclimate_sampling_sites:acl:read',
        'paleoclimate_sampling_sites:create',
        'paleoclimate_sampling_sites:export',
    ])]
    private ?string $description = null;

    #[ORM\Column(name: 'the_geom', type: 'geography_point', nullable: true, options: ['srid' => 4326])]
    private Point $point;

    #[ORM\ManyToOne(targetEntity: Region::class)]
    #[ORM\JoinColumn(name: 'region_id', referencedColumnName: 'id', nullable: false, onDelete: 'RESTRICT')]
    #[Groups([
        'paleoclimate_sampling_sites:acl:read',
        'paleoclimate_sampling_sites:create',
        'paleoclimate_sampling_sites:export',
    ])]
    private Region $region;

    #[ORM\OneToMany(
        targetEntity: MediaObjectPaleoclimateSamplingSite::class,
        mappedBy: 'item',
        orphanRemoval: true
    )]
    private Collection $mediaObjects;

    #[ORM\OneToMany(targetEntity: PaleoclimateSample::class, mappedBy: 'site')]
    private Collection $samples;

    public function __construct()
    {
        $this->samples = new ArrayCollection();
        $this->mediaObjects = new ArrayCollection();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function setCode(string $code): self
    {
        $this->code = strtoupper($code);

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

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

    public function getPoint(): Point
    {
        return $this->point;
    }

    public function setPoint(Point $point): self
    {
        $this->point = $point;

        return $this;
    }

    #[Groups([
        'paleoclimate_sampling_sites:acl:read',
    ])]
    public function getN(): float
    {
        return $this->point->getLatitude();
    }

    #[Groups([
        'paleoclimate_sampling_sites:create',
    ])]
    public function setN(float $n): self
    {
        $this->point = isset($this->point) ? clone $this->point : new Point(0, 0);
        $this->point->setLatitude($n);

        return $this;
    }

    #[Groups([
        'paleoclimate_sampling_sites:acl:read',
    ])]
    public function getE(): float
    {
        return $this->point->getLongitude();
    }

    #[Groups([
        'paleoclimate_sampling_sites:create',
    ])]
    public function setE(float $e): self
    {
        $this->point = isset($this->point) ? clone $this->point : new Point(0, 0);
        $this->point->setLongitude($e);

        return $this;
    }

    public function getRegion(): Region
    {
        return $this->region;
    }

    public function setRegion(Region $region): self
    {
        $this->region = $region;

        return $this;
    }
}
