<?php

namespace App\Entity\Vocabulary\History;

use ApiPlatform\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
// use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use App\Doctrine\Filter\SearchPropertyAliasFilter;
use App\Entity\Vocabulary\Zoo\Taxonomy;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ORM\Table(
    name: 'history_animals',
    schema: 'vocabulary'
)]
#[ORM\UniqueConstraint(columns: ['value'])]
#[ApiResource(
    shortName: 'VocHistoryAnimal',
    operations: [
        new Get(
            uriTemplate: '/vocabulary/history/animals/{id}',
        ),
        new GetCollection(
            uriTemplate: '/vocabulary/history/animals',
            order: ['value' => 'ASC'],
        ),
        new GetCollection(
            uriTemplate: '/data/vocabulary/history/animals',
            paginationEnabled: true,
            order: ['id' => 'DESC'],
            normalizationContext: ['groups' => ['voc_history_animal:acl:read']],
        ),
        new Post(
            uriTemplate: '/vocabulary/history/animals',
            denormalizationContext: ['groups' => ['voc_history_animal:create']],
            securityPostDenormalize: 'is_granted("create", object)',
            validationContext: ['groups' => ['validation:voc_history_animal:create']],
        ),
        //        new Patch(
        //            uriTemplate: '/vocabulary/history/animals/{id}',
        //            denormalizationContext: ['groups' => ['voc_history_animal:update']],
        //            security: 'is_granted("update", object)',
        //            validationContext: ['groups' => ['validation:voc_history_animal:update']],
        //        ),
        new Delete(
            uriTemplate: '/vocabulary/history/animals/{id}',
            security: 'is_granted("delete", object)'
        ),
    ],
    normalizationContext: ['groups' => ['voc_history_animal:read']],
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
#[UniqueEntity(
    fields: ['value'],
    message: 'Duplicate value: {{ value }}.',
    groups: ['validation:voc_history_animal:create']
)]
#[ApiFilter(OrderFilter::class, properties: ['id', 'value', 'taxonomy.value', 'taxonomy.vernacularName', 'taxonomy.class', 'taxonomy.family'])]
class Animal
{
    #[
        ORM\Id,
        ORM\GeneratedValue(strategy: 'SEQUENCE'),
        ORM\Column(type: 'smallint')
    ]
    #[Groups([
        'voc_history_animal:read',
        'voc_history_animal:acl:read',
    ])]
    private int $id;

    #[ORM\ManyToOne(targetEntity: Taxonomy::class)]
    #[ORM\JoinColumn(name: 'taxonomy_id', nullable: true, onDelete: 'RESTRICT')]
    #[Groups([
        'voc_history_animal:acl:read',
        'voc_history_animal:create',
        'history_animal:export',
    ])]
    private ?Taxonomy $taxonomy = null;

    #[ORM\Column(type: 'string')]
    #[Groups([
        'voc_history_animal:read',
        'voc_history_animal:acl:read',
        'history_animal:acl:read',
        'history_animal:export',
        'voc_history_animal:create',
    ])]
    #[Assert\NotBlank(groups: [
        'validation:voc_history_animal:create',
    ])]
    #[ApiProperty(required: true)]
    private string $value;

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): Animal
    {
        $this->id = $id;

        return $this;
    }

    public function getTaxonomy(): ?Taxonomy
    {
        return $this->taxonomy;
    }

    public function setTaxonomy(?Taxonomy $taxonomy): Animal
    {
        $this->taxonomy = $taxonomy;

        return $this;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function setValue(string $value): Animal
    {
        $this->value = $value;

        return $this;
    }
}
