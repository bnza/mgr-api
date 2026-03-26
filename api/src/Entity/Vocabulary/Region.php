<?php

namespace App\Entity\Vocabulary;

use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use App\Doctrine\Filter\UnaccentedSearchFilter;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity]
#[ORM\Table(
    name: 'regions',
    schema: 'vocabulary'
)]
#[ApiResource(
    shortName: 'VocRegions',
    description: 'Project\'s regions vocabulary.',
    operations: [
        new GetCollection(
            uriTemplate: '/regions',
            order: ['value' => 'ASC'],
        ),
        new Get(
            uriTemplate: '/regions/{id}',
        ),
    ],
    routePrefix: 'vocabulary',
    paginationEnabled: false
)]
#[ApiFilter(
    UnaccentedSearchFilter::class,
    properties: [
        'value',
    ]
)]
class Region
{
    #[ORM\Id,
        ORM\GeneratedValue(strategy: 'SEQUENCE'),
        ORM\Column(type: 'smallint')]
    private int $id;

    #[ORM\Column(type: 'string', unique: true)]
    #[ApiProperty(required: true)]
    #[Groups([
        'voc_history_location:read',
        'voc_history_location:acl:read',
        'history_plant:acl:read',
        'history_plant:export',
        'history_animal:export',
        'history_animal:acl:read',
        'archaeological_site:acl:read',
        'archaeological_site:export',
        'paleoclimate_sampling_sites:acl:read',
        'paleoclimate_sampling_sites:export',
        'sampling_site:acl:read',
        'sampling_site:export',
    ])]
    #[ApiProperty(required: true)]
    private string $value;

    #[ORM\Column(type: 'string', nullable: true)]
    #[Groups([
        'voc_history_location:read',
        'voc_history_location:acl:read',
    ])]
    private ?string $description = null;

    public function getId(): int
    {
        return $this->id;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function setValue(string $value): Region
    {
        $this->value = $value;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): Region
    {
        $this->description = $description;

        return $this;
    }
}
