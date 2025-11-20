<?php

namespace App\Entity\Vocabulary\Zoo;

use ApiPlatform\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ORM\Table(
    name: 'zoo_taxonomy',
    schema: 'vocabulary'
)]
#[ORM\UniqueConstraint(columns: ['code'])]
#[ORM\UniqueConstraint(columns: ['value'])]
#[ApiResource(
    shortName: 'VocZooTaxonomy',
    operations: [
        new GetCollection(
            uriTemplate: '/vocabulary/zoo/taxonomies',
            order: ['value' => 'ASC'],
        ),
        new GetCollection(
            uriTemplate: '/data/vocabulary/zoo/taxonomies',
            paginationEnabled: true,
            order: ['id' => 'DESC'],
            normalizationContext: ['groups' => ['voc_zoo_taxonomy:acl:read']],
        ),
        new Get(
            uriTemplate: '/vocabulary/zoo/taxonomies/{id}',
        ),
        new Post(
            uriTemplate: '/vocabulary/zoo/taxonomies',
            denormalizationContext: ['groups' => ['voc_zoo_taxonomy:create']],
            securityPostDenormalize: 'is_granted("create", object)',
            validationContext: ['groups' => ['validation:voc_zoo_taxonomy:create']],
        ),
        new Patch(
            uriTemplate: '/vocabulary/zoo/taxonomies/{id}',
            denormalizationContext: ['groups' => ['voc_zoo_taxonomy:update']],
            security: 'is_granted("update", object)',
            validationContext: ['groups' => ['validation:voc_zoo_taxonomy:update']],
        ),
        new Delete(
            uriTemplate: '/vocabulary/zoo/taxonomies/{id}',
            security: 'is_granted("delete", object)'
        ),
    ],
    normalizationContext: ['groups' => ['voc_zoo_taxonomy:read']],
    paginationEnabled: false,
)]
#[ApiFilter(OrderFilter::class, properties: ['id', 'code', 'value', 'vernacularName', 'class', 'family'])]
#[UniqueEntity(
    fields: ['value'],
    message: 'Duplicate taxonomy value: {{ value }}.',
    groups: ['validation:voc_zoo_taxonomy:create']
)]
#[UniqueEntity(
    fields: ['code'],
    message: 'Duplicate taxonomy code: {{ value }}.',
    groups: ['validation:voc_zoo_taxonomy:create']
)]
class Taxonomy
{
    #[
        ORM\Id,
        ORM\GeneratedValue(strategy: 'SEQUENCE'),
        ORM\Column(type: 'smallint')
    ]
    #[Groups([
        'voc_zoo_taxonomy:read',
        'voc_zoo_taxonomy:acl:read',
    ])]
    private int $id;

    #[ORM\Column(type: 'string')]
    #[Groups([
        'voc_zoo_taxonomy:read',
        'voc_zoo_taxonomy:acl:read',
        'voc_zoo_taxonomy:create',
        'voc_history_animal:acl:read',
    ])]
    #[Assert\NotBlank(groups: [
        'validation:voc_zoo_taxonomy:create',
    ])]
    #[ApiProperty(required: true)]
    private string $code;

    #[ORM\Column(type: 'string')]
    #[Groups([
        'voc_zoo_taxonomy:read',
        'voc_zoo_taxonomy:acl:read',
        'voc_zoo_taxonomy:create',
        'voc_history_animal:acl:read',
    ])]
    #[Assert\NotBlank(groups: [
        'validation:voc_zoo_taxonomy:create',
    ])]
    #[ApiProperty(required: true)]
    private string $value;

    #[ORM\Column(type: 'string')]
    #[Groups([
        'voc_zoo_taxonomy:read',
        'voc_zoo_taxonomy:acl:read',
        'voc_zoo_taxonomy:create',
        'voc_zoo_taxonomy:update',
        'voc_history_animal:acl:read',
    ])]
    #[Assert\NotBlank(groups: [
        'validation:voc_zoo_taxonomy:create',
    ])]
    #[ApiProperty(required: true)]
    private string $vernacularName;

    #[ORM\Column(type: 'string')]
    #[Groups([
        'voc_zoo_taxonomy:read',
        'voc_zoo_taxonomy:acl:read',
        'voc_zoo_taxonomy:create',
        'voc_zoo_taxonomy:update',
        'voc_history_animal:acl:read',
    ])]
    #[Assert\NotBlank(groups: [
        'validation:voc_zoo_taxonomy:create',
    ])]
    #[ApiProperty(required: true)]
    private string $class;
    #[ORM\Column(type: 'string', nullable: true)]
    #[Groups([
        'voc_zoo_taxonomy:read',
        'voc_zoo_taxonomy:acl:read',
        'voc_zoo_taxonomy:create',
        'voc_zoo_taxonomy:update',
        'voc_history_animal:acl:read',
    ])]
    private ?string $family = null;

    public function getId(): int
    {
        return $this->id;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function setCode(string $code): Taxonomy
    {
        $this->code = $code;

        return $this;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function setValue(string $value): Taxonomy
    {
        $this->value = $value;

        return $this;
    }

    public function getVernacularName(): string
    {
        return $this->vernacularName;
    }

    public function setVernacularName(string $vernacularName): Taxonomy
    {
        $this->vernacularName = $vernacularName;

        return $this;
    }

    public function getClass(): string
    {
        return $this->class;
    }

    public function setClass(string $class): Taxonomy
    {
        $this->class = $class;

        return $this;
    }

    public function getFamily(): ?string
    {
        return $this->family;
    }

    public function setFamily(?string $family): Taxonomy
    {
        $this->family = $family;

        return $this;
    }
}
