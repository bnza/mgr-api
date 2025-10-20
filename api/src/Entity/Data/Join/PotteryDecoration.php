<?php

namespace App\Entity\Data\Join;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use App\Entity\Data\Pottery;
use App\Entity\Vocabulary\Pottery\Decoration;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity]
#[ORM\Table(
    name: 'pottery_decorations',
)]
#[ORM\UniqueConstraint(columns: ['pottery_id', 'decoration_id'])]
#[ApiResource(
    operations: [
        new Get(),
        new GetCollection(),
    ],
    routePrefix: 'data',
    order: ['id' => 'DESC'],
)]
#[UniqueEntity(fields: ['pottery', 'decoration'], )]
class PotteryDecoration
{
    #[
        ORM\Id,
        ORM\GeneratedValue(strategy: 'SEQUENCE'),
        ORM\Column(type: 'bigint', unique: true)
    ]
    private int $id;

    #[ORM\ManyToOne(targetEntity: Pottery::class, inversedBy: 'decorations')]
    #[ORM\JoinColumn(name: 'pottery_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private Pottery $pottery;

    #[ORM\ManyToOne(targetEntity: Decoration::class)]
    #[ORM\JoinColumn(name: 'decoration_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    #[Groups([
        'pottery:acl:read',
    ])]
    private Decoration $decoration;

    public function getId(): int
    {
        return $this->id;
    }

    public function getPottery(): Pottery
    {
        return $this->pottery;
    }

    public function setPottery(Pottery $pottery): PotteryDecoration
    {
        $this->pottery = $pottery;

        return $this;
    }

    public function getDecoration(): Decoration
    {
        return $this->decoration;
    }

    public function setDecoration(Decoration $decoration): PotteryDecoration
    {
        $this->decoration = $decoration;

        return $this;
    }
}
