<?php
declare(strict_types=1);

namespace App\Entity\Auth;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use App\Entity\Data\Site;
use App\State\CurrentUserProvider;
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

#[Entity]
#[Table(name: 'users', schema: 'auth')]
#[ApiResource(
    operations: [
        new Get(
            requirements: ['id' => '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}']
        ),
        new Get(
            uriTemplate: '/users/me',
            security: 'is_granted("IS_AUTHENTICATED_FULLY")',
            provider: CurrentUserProvider::class,
        ),
        new GetCollection(),
    ],
    normalizationContext: ['groups' => ['user:read']],
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
    private Uuid $id;

    #[ORM\Column(type: 'string', length: 180, unique: true)]
    private string $email;

    #[ORM\Column]
    private string $password;

    private ?string $plainPassword = null;

    #[ORM\Column(type: 'simple_array')]
    private array $roles = ['ROLE_USER'];

    #[ORM\OneToMany(targetEntity: Site::class, mappedBy: 'createdBy')]
    private Collection $createdSites;

    #[ORM\OneToMany(targetEntity: SiteUserPrivilege::class, mappedBy: 'user')]
    private Collection $sitePrivileges;

    public function __construct()
    {
        $this->sitePrivileges = new ArrayCollection();
    }

    #[Groups(['user:read'])]
    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    #[Groups([
        'site:read',
        'site_user_privilege:read',
        'user:read',
    ])]
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

    #[Groups(['user:read'])]
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

    public function getSitePrivilege(Site $site): ?int
    {
        $siteId = $site->getId();

        return $this->sitePrivileges->findFirst(function (int $key, SiteUserPrivilege $sitePrivilege) use ($siteId) {
            return $sitePrivilege->getSite()->getId() === $siteId;
        });

    }


    #[Groups([
        'site:read',
        'site_user_privilege:read',
        'user:read',
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
