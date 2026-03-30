<?php

namespace App\Entity\Vocabulary\History;

use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ORM\Table(
    name: 'history_authors',
    schema: 'vocabulary'
)]
#[ORM\UniqueConstraint(columns: ['value'])]
#[ApiResource(
    shortName: 'VocHistoryAuthor',
    operations: [
        new GetCollection(
            uriTemplate: '/history/authors',
            order: ['value' => 'ASC'],
        ),
        new Get(
            uriTemplate: '/history/authors/{id}',
        ),
        new Post(
            uriTemplate: '/history/authors',
            denormalizationContext: ['groups' => ['voc_history_authors:create']],
            securityPostDenormalize: 'is_granted("create", object)',
            validationContext: ['groups' => ['validation:voc_history_authors:create']],
        ),
        new Delete(
            uriTemplate: '/history/authors/{id}',
            security: 'is_granted("delete", object)'
        ),
    ],
    routePrefix: 'vocabulary',
    paginationEnabled: false
)]
#[ApiFilter(
    SearchFilter::class,
    properties: [
        'value' => 'ipartial',
    ]
)]
#[UniqueEntity(
    fields: ['value'],
    message: 'Duplicate value: {{ value }}.',
    groups: ['validation:voc_history_authors:create']
)]
class Author
{
    #[ORM\Id,
        ORM\Column(type: 'smallint'),
        ORM\GeneratedValue(strategy: 'SEQUENCE'),]
    public int $id;

    #[ORM\Column(type: 'string')]
    #[ApiProperty(required: true)]
    #[Assert\NotBlank(groups: [
        'validation:voc_history_authors:create',
    ])]
    #[Groups(['voc_history_authors:create'])]
    public string $value;

    #[ORM\Column(type: 'string', nullable: true)]
    #[Groups(['voc_history_authors:create'])]
    public ?string $variant = null;
}
