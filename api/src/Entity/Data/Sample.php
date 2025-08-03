<?php

namespace App\Entity\Data;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use App\Entity\Vocabulary\Sample\Type;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\SequenceGenerator;
use Doctrine\ORM\Mapping\Table;
use Symfony\Component\Serializer\Annotation\Groups;

#[Entity]
#[Table(
    name: 'samples',
)]
#[ORM\UniqueConstraint(columns: ['site_id', 'number'])]
#[ORM\HasLifecycleCallbacks]
#[ApiResource(
    operations: [
        new Get(),
        new GetCollection(),
    ],
    normalizationContext: ['groups' => ['sample:acl:read']],
)]
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

    #[ORM\ManyToOne(targetEntity: Type::class)]
    #[ORM\JoinColumn(name: 'type_id', referencedColumnName: 'id', onDelete: 'RESTRICT')]
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

    public function getType(): Type
    {
        return $this->type;
    }

    public function setType(Type $type): Sample
    {
        $this->type = $type;

        return $this;
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

    public function getSite(): ?Site
    {
        return $this->stratigraphicUnit?->getSite() ?? $this->getContext()?->getSite();
    }

    #[Groups([
        'sample:acl:read',
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
