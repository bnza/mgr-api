<?php

namespace App\Entity\Vocabulary\History;

use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use App\Doctrine\Filter\SearchPropertyAliasFilter;
use App\Entity\Vocabulary\Botany\Taxonomy;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity]
#[ORM\Table(
    name: 'history_plants',
    schema: 'vocabulary'
)]
#[ORM\UniqueConstraint(columns: ['value'])]
#[ApiResource(
    shortName: 'VocZooBone',
    operations: [
        new Get(
            uriTemplate: '/history/plants/{id}',
        ),
        new GetCollection(
            uriTemplate: '/history/plants',
            order: ['value' => 'ASC'],
        ),
    ],
    routePrefix: 'vocabulary',
    paginationEnabled: false
)]
#[ApiFilter(
    SearchFilter::class,
    properties: [
        'value' => 'ipartial',
    ],
    alias: 'search'
)]
#[ApiFilter(
    SearchPropertyAliasFilter::class,
    properties: [
        'search' => 'value',
    ]
)]
class Plant
{
    #[
        ORM\Id,
        ORM\GeneratedValue(strategy: 'SEQUENCE'),
        ORM\Column(type: 'smallint')
    ]
    private int $id;

    #[ORM\ManyToOne(targetEntity: Taxonomy::class)]
    #[ORM\JoinColumn(name: 'taxonomy_id', nullable: true, onDelete: 'RESTRICT')]
    private ?Taxonomy $taxonomy = null;

    #[ORM\Column(type: 'string')]
    #[Groups([
        'history_plant:acl:read',
    ])]
    private string $value;

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): Plant
    {
        $this->id = $id;

        return $this;
    }

    public function getTaxonomy(): ?Taxonomy
    {
        return $this->taxonomy;
    }

    public function setTaxonomy(?Taxonomy $taxonomy): Plant
    {
        $this->taxonomy = $taxonomy;

        return $this;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function setValue(string $value): Plant
    {
        $this->value = $value;

        return $this;
    }
}
