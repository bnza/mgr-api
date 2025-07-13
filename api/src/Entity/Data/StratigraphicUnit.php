<?php

namespace App\Entity\Data;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\SequenceGenerator;
use Doctrine\ORM\Mapping\Table;

#[Entity]
#[Table(
    name: 'sus',
)]
#[ORM\UniqueConstraint(columns: ['site_id', 'number'])]
class StratigraphicUnit
{
    #[
        ORM\Id,
        ORM\GeneratedValue(strategy: 'SEQUENCE'),
        ORM\Column(type: 'bigint', unique: true)
    ]
    #[SequenceGenerator(sequenceName: 'context_id_seq')]
    private int $id;

    #[ORM\ManyToOne(targetEntity: Site::class)]
    #[ORM\JoinColumn(name: 'site_id', nullable: false, onDelete: 'RESTRICT')]
    private Site $site;

    #[ORM\Column(type: 'integer')]
    private int $year;

    #[ORM\Column(type: 'integer')]
    private int $number;

    #[ORM\Column(type: 'text', nullable: true)]
    private string $description;

    #[ORM\Column(type: 'text', nullable: true)]
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
}
