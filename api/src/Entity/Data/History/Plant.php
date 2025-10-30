<?php

namespace App\Entity\Data\History;

use ApiPlatform\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use App\Entity\Auth\User;
use App\Entity\Vocabulary\History\Plant as VocabularyPlant;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\SequenceGenerator;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity]
#[ORM\Table(
    name: 'history_plants'
)]
#[ApiResource(
    shortName: 'HistoryPlant',
    operations: [
        new Get(
            uriTemplate: '/plants/{id}',
        ),
        new GetCollection(
            uriTemplate: '/plants',
        ),
    ],
    routePrefix: 'data/history',
    normalizationContext: ['groups' => ['history_plant:acl:read']],
    denormalizationContext: ['groups' => ['history_plant:create']],
    order: ['id' => 'DESC'],
)]
#[ApiFilter(OrderFilter::class, properties: ['plant.value', 'location.name', 'chronologyLower', 'chronologyUpper', 'reference', 'createdBy.email'])]
class Plant
{
    #[
        ORM\Id,
        ORM\GeneratedValue(strategy: 'SEQUENCE'),
        ORM\Column(type: 'bigint', unique: true)
    ]
    #[SequenceGenerator(sequenceName: 'history_cit_item_id_seq')]
    #[Groups([
        'history_plant:acl:read',
    ])]
    private int $id;

    #[ORM\ManyToOne(targetEntity: VocabularyPlant::class)]
    #[ORM\JoinColumn(name: 'plant_id', referencedColumnName: 'id', nullable: false, onDelete: 'RESTRICT')]
    #[Groups(['history_plant:acl:read'])]
    private VocabularyPlant $plant;

    #[ORM\ManyToOne(targetEntity: Location::class)]
    #[ORM\JoinColumn(name: 'location_id', referencedColumnName: 'id', nullable: false, onDelete: 'RESTRICT')]
    #[Groups(['history_plant:acl:read'])]
    private Location $location;

    #[ORM\Column(type: 'smallint')]
    #[Groups([
        'history_plant:acl:read',
    ])]
    private int $chronologyLower;

    #[ORM\Column(type: 'smallint')]
    #[Groups([
        'history_plant:acl:read',
    ])]
    private int $chronologyUpper;

    #[ORM\Column(type: 'string')]
    #[Groups([
        'history_plant:acl:read',
    ])]
    private string $reference;

    #[ORM\Column(type: 'string', nullable: true)]
    #[Groups([
        'history_plant:acl:read',
    ])]
    private ?string $notes;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'created_by_id', referencedColumnName: 'id', onDelete: 'RESTRICT')]
    #[Groups([
        'history_plant:acl:read',
    ])]
    private User $createdBy;

    public function getId(): int
    {
        return $this->id;
    }

    public function getPlant(): VocabularyPlant
    {
        return $this->plant;
    }

    public function setPlant(VocabularyPlant $plant): Plant
    {
        $this->plant = $plant;

        return $this;
    }

    public function getLocation(): Location
    {
        return $this->location;
    }

    public function setLocation(Location $location): Plant
    {
        $this->location = $location;

        return $this;
    }

    public function getChronologyLower(): int
    {
        return $this->chronologyLower;
    }

    public function setChronologyLower(int $chronologyLower): Plant
    {
        $this->chronologyLower = $chronologyLower;

        return $this;
    }

    public function getChronologyUpper(): int
    {
        return $this->chronologyUpper;
    }

    public function setChronologyUpper(int $chronologyUpper): Plant
    {
        $this->chronologyUpper = $chronologyUpper;

        return $this;
    }

    public function getReference(): string
    {
        return $this->reference;
    }

    public function setReference(string $reference): Plant
    {
        $this->reference = $reference;

        return $this;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): Plant
    {
        $this->notes = $notes;

        return $this;
    }

    public function getCreatedBy(): User
    {
        return $this->createdBy;
    }

    public function setCreatedBy(User $createdBy): Plant
    {
        $this->createdBy = $createdBy;

        return $this;
    }
}
