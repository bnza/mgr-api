<?php

namespace App\Entity\Data;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Table;

#[Entity]
#[Table(
    name: 'analyses',
)]
class Analysis
{
    #[
        ORM\Id,
        ORM\GeneratedValue(strategy: 'SEQUENCE'),
        ORM\Column(type: 'bigint', unique: true)
    ]
    private int $id;

    #[ORM\Column(type: 'smallint')]
    private int $type = 0;

    #[ORM\Column(type: 'smallint')]
    private int $status = 0;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description;

    #[ORM\ManyToOne(targetEntity: StratigraphicUnit::class)]
    #[ORM\JoinColumn(name: 'su_id', referencedColumnName: 'id', nullable: false, onDelete: 'RESTRICT')]
    private ?StratigraphicUnit $stratigraphicUnit = null;

    #[ORM\ManyToOne(targetEntity: Context::class)]
    #[ORM\JoinColumn(name: 'context_id', referencedColumnName: 'id', nullable: false, onDelete: 'RESTRICT')]
    private ?Context $context = null;

    #[ORM\ManyToOne(targetEntity: Sample::class)]
    #[ORM\JoinColumn(name: 'sample_id', referencedColumnName: 'id', nullable: false, onDelete: 'RESTRICT')]
    private ?Context $sample = null;
}
