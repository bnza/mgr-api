<?php

namespace App\Entity\Data\History;

use ApiPlatform\Doctrine\Orm\Filter\ExistsFilter;
use ApiPlatform\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Doctrine\Orm\Filter\RangeFilter;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use App\Doctrine\Filter\UnaccentedSearchFilter;
use App\Entity\Auth\User;
use App\Entity\Vocabulary\History\Animal as VocabularyAnimal;
use App\Entity\Vocabulary\History\Location;
use App\Validator as AppAssert;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\SequenceGenerator;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ORM\Table(
    name: 'history_animals'
)]
#[ApiResource(
    shortName: 'HistoryAnimal',
    operations: [
        new Get(
            uriTemplate: '/animals/{id}',
        ),
        new GetCollection(
            uriTemplate: '/animals',
            formats: ['jsonld' => 'application/ld+json', 'csv' => 'text/csv'],
        ),
        new GetCollection(
            uriTemplate: '/locations/{parentId}/animals',
            formats: ['jsonld' => 'application/ld+json', 'csv' => 'text/csv'],
            uriVariables: [
                'parentId' => new Link(
                    toProperty: 'location',
                    fromClass: Location::class,
                ),
            ]
        ),
        new Post(
            uriTemplate: '/animals',
            securityPostDenormalize: 'is_granted("create", object)',
            validationContext: ['groups' => ['validation:history_animal:create']],
        ),
        new Patch(
            uriTemplate: '/animals/{id}',
            security: 'is_granted("update", object)',
        ),
        new Delete(
            uriTemplate: '/animals/{id}',
            security: 'is_granted("delete", object)',
        ),
    ],
    routePrefix: 'data/history',
    normalizationContext: ['groups' => ['history_animal:acl:read']],
    denormalizationContext: ['groups' => ['history_animal:create']],
    order: ['id' => 'DESC'],
)]
#[ApiFilter(
    OrderFilter::class,
    properties: [
        'animal.value',
        'location.value',
        'chronologyLower',
        'chronologyUpper',
        'reference',
        'createdBy.email',
    ])
]
#[ApiFilter(
    SearchFilter::class,
    properties: [
        'animal' => 'exact',
        'location' => 'exact',
        'chronologyLower' => 'exact',
        'chronologyUpper' => 'exact',
        'createdBy.email' => 'exact',
    ]
)]
#[ApiFilter(
    RangeFilter::class,
    properties: [
        'chronologyLower',
        'chronologyUpper']
)]
#[ApiFilter(
    ExistsFilter::class,
    properties: [
        'notes',
    ])]
#[ApiFilter(
    UnaccentedSearchFilter::class,
    properties: [
        'reference',
        'notes',
    ]
)]
class Animal
{
    #[
        ORM\Id,
        ORM\GeneratedValue(strategy: 'SEQUENCE'),
        ORM\Column(type: 'bigint', unique: true)
    ]
    #[SequenceGenerator(sequenceName: 'history_cit_item_id_seq')]
    #[Groups([
        'history_animal:acl:read',
        'history_animal:export',
    ])]
    private int $id;

    #[ORM\ManyToOne(targetEntity: VocabularyAnimal::class)]
    #[ORM\JoinColumn(name: 'animal_id', referencedColumnName: 'id', nullable: false, onDelete: 'RESTRICT')]
    #[Groups([
        'history_animal:acl:read',
        'history_animal:export',
        'history_animal:create',
    ])]
    #[Assert\NotBlank(groups: [
        'validation:history_animal:create',
    ])]
    private VocabularyAnimal $animal;

    #[ORM\ManyToOne(targetEntity: Location::class, inversedBy: 'animals')]
    #[ORM\JoinColumn(name: 'location_id', referencedColumnName: 'id', nullable: false, onDelete: 'RESTRICT')]
    #[Groups([
        'history_animal:acl:read',
        'history_animal:export',
        'history_animal:create',
    ])]
    #[Assert\NotBlank(groups: [
        'validation:history_animal:create',
    ])]
    private Location $location;

    #[ORM\Column(type: 'smallint')]
    #[Groups([
        'history_animal:acl:read',
        'history_animal:export',
        'history_animal:create',
    ])]
    #[Assert\NotBlank(groups: [
        'validation:history_animal:create',
    ])]
    #[Assert\GreaterThanOrEqual(value: -32768, groups: ['validation:history_animal:create'])]
    #[AppAssert\IsLessThanOrEqualToCurrentYear(groups: ['validation:history_animal:create'])]
    #[Assert\LessThanOrEqual(propertyPath: 'chronologyUpper', groups: ['validation:history_animal:create'])]
    private int $chronologyLower;

    #[ORM\Column(type: 'smallint')]
    #[Groups([
        'history_animal:acl:read',
        'history_animal:export',
        'history_animal:create',
    ])]
    #[Assert\NotBlank(groups: [
        'validation:history_animal:create',
    ])]
    #[Assert\GreaterThanOrEqual(value: -32768, groups: ['validation:site:create'])]
    #[AppAssert\IsLessThanOrEqualToCurrentYear(groups: ['validation:site:create'])]
    #[Assert\GreaterThanOrEqual(propertyPath: 'chronologyLower', groups: ['validation:site:create'])]
    private int $chronologyUpper;

    #[ORM\Column(type: 'string')]
    #[Groups([
        'history_animal:acl:read',
        'history_animal:export',
        'history_animal:create',
    ])]
    #[Assert\NotBlank(groups: [
        'validation:history_animal:create',
    ])]
    private string $reference;

    #[ORM\Column(type: 'string', nullable: true)]
    #[Groups([
        'history_animal:acl:read',
        'history_animal:export',
        'history_animal:create',
    ])]
    private ?string $notes;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'created_by_id', referencedColumnName: 'id', onDelete: 'RESTRICT')]
    #[Groups([
        'history_animal:acl:read',
    ])]
    private User $createdBy;

    public function getId(): int
    {
        return $this->id;
    }

    public function getAnimal(): VocabularyAnimal
    {
        return $this->animal;
    }

    public function setAnimal(VocabularyAnimal $animal): Animal
    {
        $this->animal = $animal;

        return $this;
    }

    public function getLocation(): Location
    {
        return $this->location;
    }

    public function setLocation(Location $location): Animal
    {
        $this->location = $location;

        return $this;
    }

    public function getChronologyLower(): int
    {
        return $this->chronologyLower;
    }

    public function setChronologyLower(int $chronologyLower): Animal
    {
        $this->chronologyLower = $chronologyLower;

        return $this;
    }

    public function getChronologyUpper(): int
    {
        return $this->chronologyUpper;
    }

    public function setChronologyUpper(int $chronologyUpper): Animal
    {
        $this->chronologyUpper = $chronologyUpper;

        return $this;
    }

    public function getReference(): string
    {
        return $this->reference;
    }

    public function setReference(string $reference): Animal
    {
        $this->reference = $reference;

        return $this;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): Animal
    {
        $this->notes = $notes ?? null;

        return $this;
    }

    public function getCreatedBy(): User
    {
        return $this->createdBy;
    }

    public function setCreatedBy(User $createdBy): Animal
    {
        $this->createdBy = $createdBy;

        return $this;
    }
}
