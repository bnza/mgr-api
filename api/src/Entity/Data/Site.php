<?php

declare(strict_types=1);

namespace App\Entity\Data;

use ApiPlatform\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use App\Doctrine\Filter\SearchSiteFilter;
use App\Doctrine\Filter\UnaccentedSearchFilter;
use App\Entity\Auth\SiteUserPrivilege;
use App\Entity\Auth\User;
use App\State\SitePostProcessor;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\SequenceGenerator;
use Doctrine\ORM\Mapping\Table;
use Symfony\Component\Serializer\Annotation\Groups;

#[Entity]
#[Table(name: 'sites')]
#[ORM\HasLifecycleCallbacks]
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
            processor: SitePostProcessor::class,
        ),
    ],
    normalizationContext: ['groups' => ['site:acl:read']],
    denormalizationContext: ['groups' => ['site:create']],
)]
#[ApiFilter(OrderFilter::class, properties: ['id', 'code', 'name'])]
#[ApiFilter(
    SearchFilter::class,
    properties: [
        'code' => 'exact',
    ]
)]
#[ApiFilter(
    UnaccentedSearchFilter::class,
    properties: [
        'name',
        'description',
    ]
)]
#[ApiFilter(SearchSiteFilter::class)]
class Site
{
    #[
        ORM\Id,
        ORM\GeneratedValue(strategy: 'SEQUENCE'),
        ORM\Column(type: 'bigint', unique: true)
    ]
    #[SequenceGenerator(sequenceName: 'context_id_seq')]
    #[Groups([
        'site:acl:read',
        'site_user_privilege:acl:read',
    ])]
    private int $id;

    #[ORM\Column(type: 'string', unique: true)]
    #[Groups([
        'site:acl:read',
        'site_user_privilege:acl:read',
        'site:create',
        'sus:acl:read',
    ])]
    private string $code;

    #[ORM\Column(type: 'string', unique: true)]
    #[Groups([
        'site:acl:read',
        'site_user_privilege:acl:read',
        'site:create',
        'sus:acl:read',
    ])]
    private string $name;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups([
        'site:acl:read',
        'site:create',
    ])]
    private ?string $description;

    #[ORM\Column(type: 'datetime_immutable')]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'createdSites')]
    #[ORM\JoinColumn(referencedColumnName: 'id', onDelete: 'SET NULL')]
    #[Groups([
        'site:acl:read',
        'site_user_privilege:acl:read',
    ])]
    private ?User $createdBy = null;

    #[ORM\OneToMany(
        targetEntity: SiteUserPrivilege::class,
        mappedBy: 'site',
        orphanRemoval: true
    )]
    private Collection $userPrivileges;

    public function __construct()
    {
        $this->userPrivileges = new ArrayCollection();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): Site
    {
        $this->id = $id;

        return $this;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function setCode(string $code): Site
    {
        $this->code = $code;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?\DateTimeInterface $createdAt): Site
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getCreatedBy(): ?User
    {
        return $this->createdBy;
    }

    public function setCreatedBy(User $createdBy): Site
    {
        $this->createdBy = $createdBy;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): Site
    {
        $this->name = $name;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): Site
    {
        $this->description = '' === $description ? null : $description;

        return $this;
    }

    public function getUserPrivileges(): Collection
    {
        return $this->userPrivileges;
    }

    public function addUserPrivilege(SiteUserPrivilege $userPrivilege): Site
    {
        if (!$this->userPrivileges->contains($userPrivilege)) {
            $this->userPrivileges->add($userPrivilege);
        }

        return $this;
    }

    public function removeUserPrivilege(SiteUserPrivilege $userPrivilege): Site
    {
        $this->userPrivileges->removeElement($userPrivilege);

        return $this;
    }

    #[ORM\PrePersist]
    public function setCreatedAtValue(): void
    {
        if (null === $this->createdAt) {
            $this->createdAt = new \DateTimeImmutable();
        }
    }

    #[ORM\PostPersist]
    #[ORM\PostUpdate]
    public function refresh(PostPersistEventArgs|PostUpdateEventArgs $args): void
    {
        $entityManager = $args->getObjectManager();
        $entityManager->refresh($this);
    }
}
