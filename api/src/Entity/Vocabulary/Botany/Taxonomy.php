<?php

namespace App\Entity\Vocabulary\Botany;

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
    name: 'botany_taxonomy',
    schema: 'vocabulary'
)]
#[ORM\UniqueConstraint(columns: ['value'])]
#[ApiResource(
    shortName: 'VocBotanyTaxonomy',
    operations: [
        new GetCollection(
            uriTemplate: '/vocabulary/botany/taxonomies',
            order: ['value' => 'ASC'],
        ),
        new GetCollection(
            uriTemplate: '/data/vocabulary/botany/taxonomies',
            order: ['id' => 'DESC'],
            normalizationContext: ['groups' => ['voc_botany_taxonomy:acl:read']]
        ),
        new Get(
            uriTemplate: '/vocabulary/botany/taxonomies/{id}',
        ),
        new Post(
            uriTemplate: '/vocabulary/botany/taxonomies',
            denormalizationContext: ['groups' => ['voc_botany_taxonomy:create']],
            securityPostDenormalize: 'is_granted("create", object)',
        ),
        new Patch(
            uriTemplate: '/vocabulary/botany/taxonomies/{id}',
            denormalizationContext: ['groups' => ['voc_botany_taxonomy:update']],
            security: 'is_granted("update", object)',
            validationContext: ['groups' => ['validation:voc_botany_taxonomy:update']],
        ),
        new Delete(
            uriTemplate: '/vocabulary/botany/taxonomies/{id}',
            security: 'is_granted("delete", object)'
        ),
    ],
    normalizationContext: ['groups' => ['voc_botany_taxonomy:read']],
    validationContext: ['groups' => ['validation:voc_botany_taxonomy:create']],
    paginationEnabled: false,
)]
#[ApiFilter(OrderFilter::class, properties: ['id', 'value', 'vernacularName', 'class', 'family'])]
#[UniqueEntity(
    fields: ['value'],
    message: 'Duplicate taxonomy value: {{ value }}.',
    groups: ['validation:voc_botany_taxonomy:create']
)]
class Taxonomy
{
    #[
        ORM\Id,
        ORM\GeneratedValue(strategy: 'SEQUENCE'),
        ORM\Column(type: 'smallint')
    ]
    #[Groups([
        'voc_botany_taxonomy:read',
        'voc_botany_taxonomy:acl:read',
    ])]
    private int $id;

    #[ORM\Column(type: 'string')]
    #[Groups([
        'voc_botany_taxonomy:read',
        'voc_botany_taxonomy:acl:read',
        'voc_botany_taxonomy:create',
        'voc_history_plant:acl:read',
        'history_plant:export',
    ])]
    #[Assert\NotBlank(groups: [
        'validation:voc_botany_taxonomy:create',
    ])]
    #[ApiProperty(required: true)]
    private string $value;

    #[ORM\Column(type: 'string')]
    #[Groups([
        'voc_botany_taxonomy:read',
        'voc_botany_taxonomy:acl:read',
        'voc_botany_taxonomy:create',
        'voc_botany_taxonomy:update',
        'voc_history_plant:acl:read',
        'history_plant:export',
    ])]
    #[Assert\NotBlank(groups: [
        'validation:voc_botany_taxonomy:create',
    ])]
    #[ApiProperty(required: true)]
    private string $vernacularName;

    #[ORM\Column(type: 'string')]
    #[Groups([
        'voc_botany_taxonomy:read',
        'voc_botany_taxonomy:acl:read',
        'voc_botany_taxonomy:create',
        'voc_botany_taxonomy:update',
        'voc_history_plant:acl:read',
        'history_plant:export',
    ])]
    #[Assert\NotBlank(groups: [
        'validation:voc_botany_taxonomy:create',
    ])]
    #[ApiProperty(required: true)]
    private string $class;
    #[ORM\Column(type: 'string', nullable: true)]
    #[Groups([
        'voc_botany_taxonomy:read',
        'voc_botany_taxonomy:acl:read',
        'voc_botany_taxonomy:create',
        'voc_botany_taxonomy:update',
        'voc_history_plant:acl:read',
        'history_plant:export',
    ])]
    private ?string $family = null;

    public function getId(): int
    {
        return $this->id;
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
