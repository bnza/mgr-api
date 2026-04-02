<?php

namespace App\Entity\Vocabulary\History;

use ApiPlatform\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use App\Doctrine\Filter\UnaccentedSearchFilter;
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
        new Get(
            uriTemplate: '/vocabulary/history/authors/{id}',
        ),
        new GetCollection(
            uriTemplate: '/vocabulary/history/authors',
            order: ['value' => 'ASC'],
        ),
        new GetCollection(
            uriTemplate: '/data/vocabulary/history/authors',
            paginationEnabled: true,
            order: ['id' => 'DESC'],
            normalizationContext: ['groups' => ['voc_history_author:acl:read']],
        ),
        new Post(
            uriTemplate: '/vocabulary/history/authors',
            denormalizationContext: ['groups' => ['voc_history_author:create']],
            securityPostDenormalize: 'is_granted("create", object)',
            validationContext: ['groups' => ['validation:voc_history_author:create']],
        ),
        new Delete(
            uriTemplate: '/vocabulary/history/authors/{id}',
            security: 'is_granted("delete", object)'
        ),
    ],
    normalizationContext: ['groups' => ['voc_history_author:read']],
    paginationEnabled: false
)]
#[ApiFilter(OrderFilter::class, properties: ['id', 'value', 'variant'])]
#[ApiFilter(
    UnaccentedSearchFilter::class,
    properties: [
        'value',
        'variant',
    ]
)]
#[UniqueEntity(
    fields: ['value'],
    message: 'Duplicate value: {{ value }}.',
    groups: ['validation:voc_history_author:create']
)]
class Author
{
    #[ORM\Id,
        ORM\Column(type: 'smallint'),
        ORM\GeneratedValue(strategy: 'SEQUENCE'),]
    #[Groups([
        'voc_history_author:read',
        'voc_history_author:acl:read',
    ])]
    public int $id;

    #[ORM\Column(type: 'string')]
    #[ApiProperty(required: true)]
    #[Assert\NotBlank(groups: [
        'validation:voc_history_author:create',
    ])]
    #[Groups([
        'voc_history_author:read',
        'voc_history_author:acl:read',
        'voc_history_author:create',
        'history_written_source:acl:read',
        'history_written_source:export',
        'history_written_sources_cited_works:acl:read',
        'history_written_sources_cited_works:export',
    ])]
    public string $value;

    #[ORM\Column(type: 'string', nullable: true)]
    #[Groups([
        'voc_history_author:read',
        'voc_history_author:acl:read',
        'voc_history_author:create',
        'history_written_source:acl:read',
        'history_written_source:export',
        'history_written_sources_cited_works:acl:read',
        'history_written_sources_cited_works:export',
    ])]
    public ?string $variant = null;
}
