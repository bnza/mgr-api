<?php

declare(strict_types=1);

namespace App\Entity\Data\History;

use ApiPlatform\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use App\Doctrine\Filter\SearchPropertyAliasFilter;
use Doctrine\ORM\Mapping as ORM;
use LongitudeOne\Spatial\PHP\Types\Geography\Point;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity]
#[ORM\Table(
    name: 'history_locations',
)]
#[ORM\UniqueConstraint(fields: ['name'])]
#[ApiResource(
    shortName: 'HistoryLocation',
    operations: [
        new Get(
            uriTemplate: '/locations/{id}',
        ),
        new GetCollection(
            uriTemplate: '/locations',
        ),
    ],
    routePrefix: 'data/history',
    normalizationContext: ['groups' => ['history_location:acl:read']],
    denormalizationContext: ['groups' => ['history_location:create']],
    order: ['id' => 'DESC'],
)]
#[ApiFilter(OrderFilter::class, properties: ['name', 'point.y', 'point.x'])]
#[ApiFilter(
    SearchPropertyAliasFilter::class,
    properties: [
        'search' => 'name',
    ]
)]
#[UniqueEntity(fields: ['name'])]
class Location
{
    #[
        ORM\Id,
        ORM\GeneratedValue(strategy: 'SEQUENCE'),
        ORM\Column(type: 'bigint', unique: true)
    ]
    #[Groups([
        'history_location:acl:read',
    ])]
    private int $id;

    #[ORM\Column(type: 'string')]
    #[Groups([
        'history_location:acl:read',
        'history_plant:acl:read',
    ])]
    private string $name;

    #[ORM\Column(type: 'geography_point', options: ['srid' => 4326])]
    private Point $point;

    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): Location
    {
        $this->name = $name;

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
        'history_location:acl:read',
    ])]
    public function getN(): float
    {
        return $this->point->getY();
    }

    #[Groups([
        'history_location:acl:read',
    ])]
    public function getE(): float
    {
        return $this->point->getX();
    }
}
