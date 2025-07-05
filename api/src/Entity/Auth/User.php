<?php
declare(strict_types=1);

namespace App\Entity\Auth;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use App\Dto\Input\UserPasswordChangeInputDto;
use App\Entity\Data\Site;
use App\Repository\UserRepository;
use App\State\CurrentUserProvider;
use App\State\UserPasswordChangeProcessor;
use App\State\UserPasswordHasherProcessor;
use App\Validator\IsStrongPassword;
use App\Validator\IsValidRole;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Table;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\IdGenerator\UuidGenerator;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

#[Entity(repositoryClass: UserRepository::class)]
#[Table(name: 'users', schema: 'auth')]
#[ApiResource(
    operations: [
        new Get(
            requirements: ['id' => '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}']
        ),
        new Get(
            uriTemplate: '/users/me',
            normalizationContext: ['groups' => ['user:me:read']],
            security: 'is_granted("IS_AUTHENTICATED_FULLY")',
            provider: CurrentUserProvider::class,
        ),
        new GetCollection(),
        new Post(
            denormalizationContext: ['groups' => ['user:write']],
            validationContext: ['groups' => ['validation:user:create']],
            processor: UserPasswordHasherProcessor::class,
        ),
        new Post(
            uriTemplate: '/users/me/change_password',
            security: 'is_granted("IS_AUTHENTICATED_FULLY")',
            validationContext: ['groups' => ['validation:user:change-password']],
            input: UserPasswordChangeInputDto::class,
            output: false,
            processor: UserPasswordChangeProcessor::class,
        ),
        new Patch(
            uriTemplate: '/users/{id}/change_password',
            requirements: ['id' => '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}'],
            denormalizationContext: ['groups' => ['user:change-password']],
            validationContext: ['groups' => ['validation:user:change-password']],
            output: false,
            processor: UserPasswordChangeProcessor::class,
        ),
    ],
    normalizationContext: ['groups' => ['user:acl:read']],
    security: 'is_granted("ROLE_ADMIN")',
)]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[
        ORM\Id,
        ORM\GeneratedValue(strategy: 'CUSTOM'),
        ORM\CustomIdGenerator(class: UuidGenerator::class),
        ORM\Column(type: 'uuid', unique: true)
    ]
    #[Groups([
        'site:acl:read',
        'site_user_privilege:acl:read',
        'user:me:read',
        'user:acl:read',
    ])]
    private Uuid $id;

    #[ORM\Column(type: 'string', length: 180, unique: true)]
    #[Groups([
        'user:me:read',
        'user:acl:read',
        'user:write',
    ])]
    #[Assert\NotBlank(groups: ['validation:user:create'])]
    #[Assert\Email(groups: ['validation:user:create'])]
    private string $email;

    #[ORM\Column]
    private string $password;

    #[Groups([
        'user:write',
        'user:change-password',
    ])]
    #[Assert\NotBlank(
        groups: [
            'validation:user:create',
            'validation:user:change-password',
        ])]
    #[IsStrongPassword(
        groups: [
            'validation:user:create',
            'validation:user:change-password',
        ]
    )]
    private ?string $plainPassword = null;

    #[ORM\Column(type: 'simple_array')]
    #[Groups([
        'user:me:read',
        'user:acl:read',
        'user:write',
    ])]
    #[Assert\NotBlank(groups: ['validation:user:create'])]
    #[Assert\All(
        constraints: [
            new Assert\NotBlank(),
            new IsValidRole(),
        ],
        groups: ['validation:user:create']
    )]
    private array $roles = ['ROLE_USER'];

    #[ORM\OneToMany(targetEntity: Site::class, mappedBy: 'createdBy')]
    private Collection $createdSites;

    #[ORM\OneToMany(targetEntity: SiteUserPrivilege::class, mappedBy: 'user')]
    private Collection $sitePrivileges;

    public function __construct()
    {
        $this->sitePrivileges = new ArrayCollection();
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function getId(): ?Uuid
    {
        return $this->id;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    public function getPlainPassword(): ?string
    {
        return $this->plainPassword;
    }

    public function setPlainPassword(?string $plainPassword): self
    {
        $this->plainPassword = $plainPassword;

        return $this;
    }

    public function getRoles(): array
    {
        $roles = $this->roles;

        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    public function setRoles(array $roles): self
    {
        $this->roles = $roles;

        return $this;
    }

    public function addSitePrivilege(SiteUserPrivilege $site): User
    {
        if (!$this->sitePrivileges->contains($site)) {
            $this->sitePrivileges->add($site);
        }

        return $this;
    }

    public function getSitePrivileges(): Collection
    {
        return $this->sitePrivileges;
    }

    public function getSitePrivilege(Site $site): ?SiteUserPrivilege
    {
        $siteId = $site->getId();

        return $this->sitePrivileges->findFirst(function (int $key, SiteUserPrivilege $sitePrivilege) use ($siteId) {
            return $sitePrivilege->getSite()->getId() === $siteId;
        });

    }

    #[Groups([
        'site:acl:read',
        'site_user_privilege:acl:read',
        'user:acl:read',
    ])]
    public function getUserIdentifier(): string
    {
        return $this->email;
    }

    public function eraseCredentials(): void
    {
        $this->plainPassword = null;
    }
}
