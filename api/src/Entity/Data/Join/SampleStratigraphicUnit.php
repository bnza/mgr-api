<?php

namespace App\Entity\Data\Join;

use ApiPlatform\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Post;
use App\Entity\Data\Sample;
use App\Entity\Data\StratigraphicUnit;
use App\Validator as AppAssert;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ORM\Table(
    name: 'sample_stratigraphic_units',
)]
#[ApiResource(
    operations: [
        new Get(),
        new GetCollection(),
        new GetCollection(
            uriTemplate: '/stratigraphic_units/{parentId}/samples',
            uriVariables: [
                'parentId' => new Link(
                    toProperty: 'stratigraphicUnit',
                    fromClass: StratigraphicUnit::class,
                ),
            ],
            normalizationContext: [
                'groups' => ['sample_stratigraphic_unit:samples:acl:read', 'sample:acl:read'],
            ],
        ),
        new GetCollection(
            uriTemplate: '/samples/{parentId}/stratigraphic_units',
            uriVariables: [
                'parentId' => new Link(
                    toProperty: 'sample',
                    fromClass: Sample::class,
                ),
            ],
            normalizationContext: [
                'groups' => ['sample_stratigraphic_unit:stratigraphic_units:acl:read', 'sus:acl:read'],
            ],
        ),
        new Post(
            securityPostDenormalize: 'is_granted("create", object)',
            validationContext: ['groups' => ['validation:sample_stratigraphic_unit:create']],
        ),
        new Delete(
            security: 'is_granted("delete", object)',
        ),
    ],
    routePrefix: 'data',
    normalizationContext: [
        'groups' => ['sample_stratigraphic_unit:acl:read', 'sample:acl:read', 'sus:acl:read'],
    ],
)]
#[ApiFilter(
    OrderFilter::class,
    properties: [
        'id',
        // Mirror Sample sortable properties (excluding id)
        'sample.year',
        'sample.number',
        'sample.site.code',
        // Mirror StratigraphicUnit sortable properties (excluding id)
        'stratigraphicUnit.year',
        'stratigraphicUnit.number',
        'stratigraphicUnit.site.code',
    ],
)]
#[UniqueEntity(
    fields: ['sample', 'stratigraphicUnit'],
    message: 'Duplicate [sample, stratigraphic unit] combination.',
    groups: ['validation:sample_stratigraphic_unit:create']
)]
#[AppAssert\BelongToTheSameSite(groups: ['validation:sample_stratigraphic_unit:create'])]
class SampleStratigraphicUnit
{
    #[
        ORM\Id,
        ORM\GeneratedValue(strategy: 'SEQUENCE'),
        ORM\Column(type: 'bigint', unique: true)
    ]
    #[Groups([
        'sample_stratigraphic_unit:acl:read',
        'sample_stratigraphic_unit:stratigraphic_units:acl:read',
        'sample_stratigraphic_unit:samples:acl:read',
    ])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Sample::class, inversedBy: 'sampleStratigraphicUnits')]
    #[ORM\JoinColumn(name: 'sample_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    #[Groups([
        'sample_stratigraphic_unit:acl:read',
        'sample_stratigraphic_unit:samples:acl:read',
    ])]
    #[Assert\NotBlank(groups: ['validation:sample_stratigraphic_unit:create'])]
    private ?Sample $sample = null;

    #[ORM\ManyToOne(targetEntity: StratigraphicUnit::class, inversedBy: 'stratigraphicUnitSamples')]
    #[ORM\JoinColumn(name: 'su_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    #[Groups([
        'sample_stratigraphic_unit:acl:read',
        'sample_stratigraphic_unit:stratigraphic_units:acl:read',
    ])]
    #[Assert\NotBlank(groups: ['validation:sample_stratigraphic_unit:create'])]
    private ?StratigraphicUnit $stratigraphicUnit = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSample(): ?Sample
    {
        return $this->sample;
    }

    public function setSample(?Sample $sample): SampleStratigraphicUnit
    {
        $this->sample = $sample;

        return $this;
    }

    public function getStratigraphicUnit(): ?StratigraphicUnit
    {
        return $this->stratigraphicUnit;
    }

    public function setStratigraphicUnit(?StratigraphicUnit $stratigraphicUnit): SampleStratigraphicUnit
    {
        $this->stratigraphicUnit = $stratigraphicUnit;

        return $this;
    }
}
