<?php

namespace App\Entity\Data;

use ApiPlatform\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use App\Doctrine\Filter\SearchContextFilter;
use App\Doctrine\Filter\UnaccentedSearchFilter;
use App\Entity\Data\Join\ContextStratigraphicUnit;
use App\Entity\Vocabulary\Context\Type;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\SequenceGenerator;
use Doctrine\ORM\Mapping\Table;
use Symfony\Component\Serializer\Annotation\Groups;

#[Entity]
#[Table(
    name: 'contexts',
)]
#[ORM\UniqueConstraint(columns: ['site_id', 'type_id', 'name'])]
#[ApiResource(
    shortName: 'Context',
    operations: [
        new Get(),
        new GetCollection(),
    ],
    routePrefix: 'data',
    normalizationContext: ['groups' => ['context:acl:read']],
)]
#[ApiFilter(
    OrderFilter::class,
    properties: ['id', 'site.code', 'name', 'type.group', 'type.value']
)]
#[ApiFilter(
    SearchFilter::class,
    properties: [
        'site' => 'exact',
    ]
)]
#[ApiFilter(
    UnaccentedSearchFilter::class,
    properties: [
        'name',
    ]
)]
#[ApiFilter(SearchContextFilter::class)]
class Context
{
    #[
        ORM\Id,
        ORM\GeneratedValue(strategy: 'SEQUENCE'),
        ORM\Column(type: 'bigint', unique: true)
    ]
    #[SequenceGenerator(sequenceName: 'context_id_seq')]
    #[Groups([
        'context:acl:read',
        'context_stratigraphic_unit:acl:read',
    ])]
    private int $id;

    #[ORM\ManyToOne(targetEntity: Type::class)]
    #[ORM\JoinColumn(name: 'type_id', referencedColumnName: 'id', onDelete: 'RESTRICT')]
    #[Groups([
        'context:acl:read',
        'context_stratigraphic_unit:acl:read',
        'context_stratigraphic_unit:stratigraphic_unit:acl:read',
    ])]
    private Type $type;

    #[ORM\ManyToOne(targetEntity: Site::class)]
    #[ORM\JoinColumn(name: 'site_id', nullable: false, onDelete: 'RESTRICT')]
    #[Groups([
        'context:acl:read',
        'context_stratigraphic_unit:acl:read',
        'context_stratigraphic_unit:stratigraphic_unit:acl:read',
    ])]
    private Site $site;

    #[ORM\OneToMany(targetEntity: ContextStratigraphicUnit::class, mappedBy: 'context')]
    #[Groups([
        'context:acl:read',
    ])]
    private Collection $contextsStratigraphicUnits;

    #[ORM\Column(type: 'string')]
    #[Groups([
        'context:acl:read',
        'context_stratigraphic_unit:acl:read',
        'context_stratigraphic_unit:stratigraphic_unit:acl:read',
    ])]
    private string $name;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups([
        'context:acl:read',
        'context_stratigraphic_unit:acl:read',
        'context_stratigraphic_unit:stratigraphic_unit:acl:read',
    ])]
    private ?string $description;

    public function __construct()
    {
        $this->contextsStratigraphicUnits = new ArrayCollection();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getType(): Type
    {
        return $this->type;
    }

    public function setType(Type $type): Context
    {
        $this->type = $type;

        return $this;
    }

    public function getSite(): Site
    {
        return $this->site;
    }

    public function setSite(Site $site): Context
    {
        $this->site = $site;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): Context
    {
        $this->name = $name;

        return $this;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): Context
    {
        $this->description = $description;

        return $this;
    }

    public function getContextsStratigraphicUnits(): Collection
    {
        return $this->contextsStratigraphicUnits;
    }

    public function setContextsStratigraphicUnits(Collection $contextsStratigraphicUnits): Context
    {
        $this->contextsStratigraphicUnits = $contextsStratigraphicUnits;

        return $this;
    }
}
