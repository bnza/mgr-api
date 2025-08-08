<?php

namespace App\Entity\Data;

use ApiPlatform\Doctrine\Orm\Filter\NumericFilter;
use ApiPlatform\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use App\Doctrine\Filter\Granted\GrantedStratigraphicUnitFilter;
use App\Doctrine\Filter\SearchStratigraphicUnitFilter;
use App\Validator as AppAssert;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\SequenceGenerator;
use Doctrine\ORM\Mapping\Table;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[Entity]
#[Table(
    name: 'sus',
)]
#[ORM\UniqueConstraint(columns: ['site_id', 'year', 'number'])]
#[ApiResource(
    operations: [
        new Get(),
        new GetCollection(),
        new GetCollection(
            uriTemplate: '/sites/{parentId}/stratigraphic_units',
            uriVariables: [
                'parentId' => new Link(
                    toProperty: 'site',
                    fromClass: Site::class,
                ),
            ]
        ),
        new Delete(
            security: 'is_granted("delete", object)',
        ),
        new Patch(
            security: 'is_granted("update", object)',
        ),
        new Post(
            securityPostDenormalize: 'is_granted("create", object)',
            validationContext: ['groups' => ['validation:su:create']],
        ),
    ],
    routePrefix: 'data',
    normalizationContext: ['groups' => ['sus:acl:read']],
)]
#[ApiFilter(OrderFilter::class, properties: ['id', 'year', 'number', 'site.code'])]
#[ApiFilter(
    SearchFilter::class,
    properties: [
        'site' => 'exact',
    ]
)]
#[ApiFilter(
    NumericFilter::class,
    properties: [
        'number',
        'year',
    ]
)]
#[ApiFilter(SearchStratigraphicUnitFilter::class)]
#[ApiFilter(GrantedStratigraphicUnitFilter::class)]
#[UniqueEntity(
    fields: ['site', 'year', 'number'],
    message: 'Duplicate [site, year, number] combination.',
    groups: ['validation:su:create']
)]
class StratigraphicUnit
{
    #[
        ORM\Id,
        ORM\GeneratedValue(strategy: 'SEQUENCE'),
        ORM\Column(type: 'bigint', unique: true)
    ]
    #[SequenceGenerator(sequenceName: 'context_id_seq')]
    #[Groups([
        'sus:acl:read',
        'context_stratigraphic_unit:acl:read',
    ])]
    private int $id;

    #[ORM\ManyToOne(targetEntity: Site::class)]
    #[ORM\JoinColumn(name: 'site_id', nullable: false, onDelete: 'RESTRICT')]
    #[Groups([
        'sus:acl:read',
    ])]
    #[Assert\NotBlank(groups: [
        'validation:su:create',
        'context_stratigraphic_unit:acl:read',
        'context_stratigraphic_unit:stratigraphic_units:acl:read',
    ])]
    private Site $site;

    #[ORM\Column(type: 'integer')]
    #[Groups([
        'sus:acl:read',
    ])]
    #[Assert\AtLeastOneOf([
        new Assert\EqualTo(value: 0, groups: ['validation:su:create']),
        new Assert\Sequentially([
            new Assert\GreaterThanOrEqual(value: 2000),
            new AppAssert\IsLessThanOrEqualToCurrentYear(),
        ],
            groups: ['validation:su:create']),
    ],
        groups: ['validation:su:create']
    )]
    private int $year = 0;

    #[ORM\Column(type: 'integer')]
    #[Groups([
        'sus:acl:read',
    ])]
    #[Assert\NotBlank(groups: [
        'validation:su:create',
    ])]
    #[Assert\Positive(groups: [
        'validation:su:create',
    ])]
    private int $number;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups([
        'sus:acl:read',
    ])]
    private string $description;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups([
        'sus:acl:read',
        'context_stratigraphic_unit:acl:read',
    ])]
    private string $interpretation;

    public function getId(): int
    {
        return $this->id;
    }

    public function getSite(): Site
    {
        return $this->site;
    }

    public function setSite(Site $site): StratigraphicUnit
    {
        $this->site = $site;

        return $this;
    }

    public function getYear(): int
    {
        return $this->year;
    }

    public function setYear(int $year): StratigraphicUnit
    {
        $this->year = $year;

        return $this;
    }

    public function getNumber(): int
    {
        return $this->number;
    }

    public function setNumber(int $number): StratigraphicUnit
    {
        $this->number = $number;

        return $this;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): StratigraphicUnit
    {
        $this->description = $description;

        return $this;
    }

    public function getInterpretation(): string
    {
        return $this->interpretation;
    }

    public function setInterpretation(string $interpretation): StratigraphicUnit
    {
        $this->interpretation = $interpretation;

        return $this;
    }

    #[Groups([
        'sus:acl:read',
        'context_stratigraphic_unit:acl:read',
    ])]
    public function getCode(): string
    {
        return sprintf('%s.%s.%u', $this->site->getCode(), substr(0 === $this->year ? '____' : $this->year, -2), $this->number);
    }
}
