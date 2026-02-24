<?php

declare(strict_types=1);

namespace App\Entity\Data;

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
use App\Repository\SamplingSiteRepository;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\SequenceGenerator;
use Doctrine\ORM\Mapping\Table;
use LongitudeOne\Spatial\PHP\Types\Geography\Point;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[Entity(repositoryClass: SamplingSiteRepository::class)]
#[Table(name: 'sampling_sites')]
#[ApiResource(
    operations: [
        new Get(
            uriTemplate: '/data/sampling_sites/{id}',
        ),
        new GetCollection(
            uriTemplate: '/data/sampling_sites',
        ),
        new Delete(
            uriTemplate: '/data/sampling_sites/{id}',
        ),
        new Patch(
            uriTemplate: '/data/sampling_sites/{id}',
        ),
        new Post(
            uriTemplate: '/data/sampling_sites',
        ),
    ],
    normalizationContext: ['groups' => ['sampling_site:read']],
    denormalizationContext: ['groups' => ['sampling_site:write']],
    order: ['id' => 'DESC'],
)]
#[ApiFilter(
    OrderFilter::class,
    properties: ['id', 'code', 'name']
)]
#[ApiFilter(
    SearchFilter::class,
    properties: [
        'code' => 'exact',
    ]
)]
#[ApiFilter(
    UnaccentedSearchFilter::class,
    properties: [
        'name',
        'description',
    ]
)]
#[UniqueEntity(
    fields: ['code'],
    message: 'Duplicate sampling site code.',
)]
#[UniqueEntity(
    fields: ['name'],
    message: 'Duplicate sampling site name.',
)]
class SamplingSite
{
    #[
        ORM\Id,
        ORM\GeneratedValue(strategy: 'SEQUENCE'),
        ORM\Column(type: 'bigint', unique: true)
    ]
    #[SequenceGenerator(sequenceName: 'context_id_seq')]
    #[Groups([
        'sampling_site:read',
    ])]
    private int $id;

    #[ORM\Column(type: 'string', unique: true)]
    #[Groups([
        'sediment_core:acl:read',
        'sampling_site:read',
        'sampling_site:write',
    ])]
    #[Assert\NotBlank]
    private string $code;

    #[ORM\Column(type: 'string', unique: true)]
    #[Groups([
        'sediment_core:acl:read',
        'sampling_site:read',
        'sampling_site:write',
    ])]
    #[Assert\NotBlank]
    private string $name;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups([
        'sampling_site:read',
        'sampling_site:write',
    ])]
    private ?string $description = null;

    #[ORM\Column(name: 'the_geom', type: 'geography_point', nullable: true, options: ['srid' => 4326])]
    private Point $point;

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
        'sampling_site:read',
    ])]
    public function getN(): float
    {
        return $this->point->getLatitude();
    }

    #[Groups([
        'sampling_site:write',
    ])]
    public function setN(float $n): self
    {
        $this->point = isset($this->point) ? clone $this->point : new Point(0, 0);
        $this->point->setLatitude($n);

        return $this;
    }

    #[Groups([
        'sampling_site:read',
    ])]
    public function getE(): float
    {
        return $this->point->getLongitude();
    }

    #[Groups([
        'sampling_site:write',
    ])]
    public function setE(float $e): self
    {
        $this->point = isset($this->point) ? clone $this->point : new Point(0, 0);
        $this->point->setLongitude($e);

        return $this;
    }
}
