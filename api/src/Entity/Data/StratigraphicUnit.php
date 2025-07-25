<?php

namespace App\Entity\Data;

use ApiPlatform\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\SequenceGenerator;
use Doctrine\ORM\Mapping\Table;
use Symfony\Component\Serializer\Annotation\Groups;

#[Entity]
#[Table(
    name: 'sus',
)]
#[ORM\UniqueConstraint(columns: ['site_id', 'number'])]
#[ApiResource(
    operations: [
        new Get(),
        new GetCollection(),
        new Delete(
            security: 'is_granted("delete", object)',
        ),
        new Patch(
            security: 'is_granted("update", object)',
        ),
        new Post(
            securityPostDenormalize: 'is_granted("create", object)',
        ),
    ],
    normalizationContext: ['groups' => ['sus:acl:read']],
)]
#[ApiFilter(OrderFilter::class, properties: ['id', 'year', 'number', 'site.code'])]
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
    ])]
    private int $id;

    #[ORM\ManyToOne(targetEntity: Site::class)]
    #[ORM\JoinColumn(name: 'site_id', nullable: false, onDelete: 'RESTRICT')]
    #[Groups([
        'sus:acl:read',
    ])]
    private Site $site;

    #[ORM\Column(type: 'integer')]
    #[Groups([
        'sus:acl:read',
    ])]
    private int $year;

    #[ORM\Column(type: 'integer')]
    #[Groups([
        'sus:acl:read',
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
    ])]
    public function getCode(): string
    {
        return sprintf('%s.%u.%u', $this->site->getCode(), substr($this->year, -2), $this->number);
    }
}
