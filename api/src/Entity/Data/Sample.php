<?php

namespace App\Entity\Data;

use ApiPlatform\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use App\Entity\Vocabulary\Sample\Type;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\SequenceGenerator;
use Doctrine\ORM\Mapping\Table;
use Symfony\Component\Serializer\Annotation\Groups;

#[Entity]
#[Table(
    name: 'samples',
)]
#[ORM\UniqueConstraint(columns: ['site_id', 'type_id', 'number'])]
#[ORM\HasLifecycleCallbacks]
#[ApiResource(
    operations: [
        new Get(),
        new GetCollection(),
    ],
    routePrefix: 'data',
    normalizationContext: ['groups' => ['sample:acl:read']],
)]
#[ApiFilter(
    OrderFilter::class,
    properties: ['id', 'site.code', 'year', 'number', 'type.code', 'type.value']
)]
class Sample
{
    #[
        ORM\Id,
        ORM\GeneratedValue(strategy: 'SEQUENCE'),
        ORM\Column(type: 'bigint', unique: true)
    ]
    #[SequenceGenerator(sequenceName: 'context_id_seq')]
    #[Groups([
        'sample:acl:read',
    ])]
    private int $id;

    #[ORM\ManyToOne(targetEntity: Site::class)]
    #[ORM\JoinColumn(name: 'site_id', nullable: false, onDelete: 'RESTRICT')]
    #[Groups([
        'sample:acl:read',
    ])]
    private Site $site;

    #[ORM\ManyToOne(targetEntity: Type::class)]
    #[ORM\JoinColumn(name: 'type_id', nullable: false, onDelete: 'RESTRICT')]
    #[Groups([
        'sample:acl:read',
    ])]
    private Type $type;

    #[ORM\Column(type: 'smallint')]
    #[Groups([
        'sample:acl:read',
    ])]
    private int $year = 0;

    #[ORM\Column(type: 'smallint')]
    #[Groups([
        'sample:acl:read',
    ])]
    private int $number;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups([
        'sample:acl:read',
    ])]
    private ?string $description;

    public function getId(): int
    {
        return $this->id;
    }

    public function getSite(): Site
    {
        return $this->site;
    }

    public function setSite(Site $site): Sample
    {
        $this->site = $site;

        return $this;
    }

    public function getType(): Type
    {
        return $this->type;
    }

    public function setType(Type $type): Sample
    {
        $this->type = $type;

        return $this;
    }

    public function getYear(): int
    {
        return $this->year;
    }

    public function setYear(?int $year): Sample
    {
        $this->year = $year ?? 0;

        return $this;
    }

    public function getNumber(): int
    {
        return $this->number;
    }

    public function setNumber(int $number): Sample
    {
        $this->number = $number;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): Sample
    {
        $this->description = $description;

        return $this;
    }

    #[Groups([
        'sample:acl:read',
        'sample_stratigraphic_unit:samples:acl:read',
    ])]
    public function getCode(): string
    {
        return sprintf(
            '%s.%s.%s.%u',
            $this->getSite()->getCode(),
            $this->type->code,
            substr(0 === $this->year ? '____' : $this->year, -2),
            $this->number
        );
    }
}
