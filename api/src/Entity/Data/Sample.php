<?php

namespace App\Entity\Data;

use Doctrine\ORM\Event\PostUpdateEventArgs;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\SequenceGenerator;
use Doctrine\ORM\Mapping\Table;

#[Entity]
#[Table(
    name: 'samples',
)]
#[ORM\UniqueConstraint(columns: ['site_id', 'number'])]
#[ORM\HasLifecycleCallbacks]
class Sample
{
    #[
        ORM\Id,
        ORM\GeneratedValue(strategy: 'SEQUENCE'),
        ORM\Column(type: 'bigint', unique: true)
    ]
    #[SequenceGenerator(sequenceName: 'context_id_seq')]
    private int $id;

    #[ORM\ManyToOne(targetEntity: StratigraphicUnit::class)]
    #[ORM\JoinColumn(name: 'su_id', referencedColumnName: 'id', onDelete: 'RESTRICT')]
    private ?StratigraphicUnit $stratigraphicUnit = null;

    #[ORM\ManyToOne(targetEntity: Context::class)]
    #[ORM\JoinColumn(name: 'context_id', referencedColumnName: 'id', onDelete: 'RESTRICT')]
    private ?Context $context = null;

    #[ORM\Column(type: 'bigint', insertable: false, updatable: false)]
    private int $siteId;

    #[ORM\Column(type: 'integer')]
    private int $year;

    #[ORM\Column(type: 'string')]
    private int $number;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description;

    public function getId(): int
    {
        return $this->id;
    }

    public function getStratigraphicUnit(): ?StratigraphicUnit
    {
        return $this->stratigraphicUnit;
    }

    public function setStratigraphicUnit(?StratigraphicUnit $stratigraphicUnit): Sample
    {
        $this->stratigraphicUnit = $stratigraphicUnit;

        return $this;
    }

    public function getContext(): ?Context
    {
        return $this->context;
    }

    public function setContext(?Context $context): Sample
    {
        $this->context = $context;

        return $this;
    }

    public function getYear(): int
    {
        return $this->year;
    }

    public function setYear(int $year): Sample
    {
        $this->year = $year;

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

    /**
     * Executes after an entity has been updated and refreshes the state of the entity because
     * triggers may change data at the db level.
     *
     * @param PostUpdateEventArgs $args the event arguments containing entity manager and entity state
     *
     * @throws ORMException
     */
    #[ORM\PostUpdate]
    public function refresh(PostUpdateEventArgs $args): void
    {
        $args->getObjectManager()->refresh($this);
    }
}
