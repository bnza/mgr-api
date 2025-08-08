<?php

namespace App\Entity\Data\Join;

use ApiPlatform\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
use App\Entity\Data\Context;
use App\Entity\Data\Sample;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ORM\Table(
    name: 'context_samples',
)]
#[ApiResource(
    operations: [
        new Get(),
        new GetCollection(),
        new GetCollection(
            uriTemplate: '/contexts/{parentId}/samples',
            uriVariables: [
                'parentId' => new Link(
                    toProperty: 'context',
                    fromClass: Context::class,
                ),
            ],
            normalizationContext: [
                'groups' => ['context_sample:samples:acl:read', 'sample:acl:read'],
            ],
        ),
        new GetCollection(
            uriTemplate: '/samples/{parentId}/contexts',
            uriVariables: [
                'parentId' => new Link(
                    toProperty: 'sample',
                    fromClass: Sample::class,
                ),
            ],
            normalizationContext: [
                'groups' => ['context_sample:contexts:acl:read', 'context:acl:read'],
            ],
        ),
    ],
    routePrefix: 'data',
    normalizationContext: [
        'groups' => ['context_sample:acl:read'],
    ],
)]
#[ApiFilter(
    OrderFilter::class,
    properties: [
        'id',
        // Mirror Context sortable properties
        'context.name',
        'context.type.group',
        'context.type.value',
        'context.site.code',
        // Mirror Sample sortable properties
        'sample.year',
        'sample.number',
        'sample.site.code',
    ],
)]
#[UniqueEntity(
    fields: ['context', 'sample'],
    message: 'Duplicate [context, sample] combination.',
    groups: ['validation:context_sample:create']
)]
class ContextSample
{
    #[
        ORM\Id,
        ORM\GeneratedValue(strategy: 'SEQUENCE'),
        ORM\Column(type: 'bigint', unique: true)
    ]
    #[Groups([
        'context_sample:acl:read',
        'context_sample:contexts:acl:read',
        'context_sample:samples:acl:read',
    ])]
    private int $id;

    #[ORM\ManyToOne(targetEntity: Context::class)]
    #[ORM\JoinColumn(name: 'context_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    #[Groups([
        'context_sample:acl:read',
        'context_sample:contexts:acl:read',
    ])]
    #[Assert\NotBlank(groups: ['validation:context_sample:create'])]
    private Context $context;

    #[ORM\ManyToOne(targetEntity: Sample::class)]
    #[ORM\JoinColumn(name: 'sample_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    #[Groups([
        'context_sample:acl:read',
        'context_sample:samples:acl:read',
    ])]
    #[Assert\NotBlank(groups: ['validation:context_sample:create'])]
    private Sample $sample;

    public function getId(): int
    {
        return $this->id;
    }

    public function getContext(): Context
    {
        return $this->context;
    }

    public function setContext(Context $context): ContextSample
    {
        $this->context = $context;

        return $this;
    }

    public function getSample(): Sample
    {
        return $this->sample;
    }

    public function setSample(Sample $sample): ContextSample
    {
        $this->sample = $sample;

        return $this;
    }
}
