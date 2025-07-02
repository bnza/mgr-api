<?php
declare(strict_types=1);

namespace App\Entity\Auth;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use App\Entity\Data\Site;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Table;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\IdGenerator\UuidGenerator;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

#[Entity]
#[Table(name: 'site_user_privileges', schema: 'auth')]
#[ORM\UniqueConstraint(columns: ['user_id', 'site_id'])]
#[ApiResource(
    operations: [
        new Get(),
        new Get(
            uriTemplate: '/sites/{siteId}/user_privileges/{id}',
            uriVariables: [
                'siteId' => new Link(
                    toProperty: 'site',
                    fromClass: Site::class,
                ),
                'id' => new Link(
                    toProperty: 'id',
                    fromClass: SiteUserPrivilege::class,
                ),
            ]
        ),
        new GetCollection(),
        new GetCollection(
            uriTemplate: '/sites/{siteId}/user_privileges',
            uriVariables: [
                'siteId' => new Link(
                    toProperty: 'site',
                    fromClass: Site::class,
                ),
            ]
        ),
        new Post(
            denormalizationContext: ['groups' => ['site_user_privilege:create']],
            securityPostDenormalize: 'is_granted("create", object)',
            validationContext: ['groups' => ['validation:site_user_privilege:create']],
        ),
        new Delete(
            security: 'is_granted("delete", object)',
            output: false,
        ),
        new Patch(
            denormalizationContext: ['groups' => ['site_user_privilege:update']],
            security: 'is_granted("delete", object)',
            validationContext: ['groups' => ['validation:site_user_privilege:update']],
        ),
    ],
    normalizationContext: ['groups' => ['site_user_privilege:read']],
    security: 'is_granted("ROLE_ADMIN") or is_granted("ROLE_EDITOR")',
)]
#[UniqueEntity(
    fields: ['user', 'site'],
    message: 'This user already has permissions set for this site.',
    groups: ['validation:site_user_privilege:create']
)]
class SiteUserPrivilege
{
    #[
        ORM\Id,
        ORM\GeneratedValue(strategy: 'CUSTOM'),
        ORM\CustomIdGenerator(class: UuidGenerator::class),
        ORM\Column(type: 'uuid', unique: true)
    ]
    private Uuid $id;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'sitePrivileges')]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    #[Groups([
        'site_user_privilege:read',
        'site_user_privilege:create',
    ])]
    #[Assert\NotBlank(groups: ['validation:site_user_privilege:create'])]
    private User $user;

    #[ORM\ManyToOne(targetEntity: Site::class, inversedBy: 'userPrivileges')]
    #[ORM\JoinColumn(name: 'site_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    #[Groups([
        'site_user_privilege:read',
        'site_user_privilege:create',
    ])]
    #[Assert\NotBlank(groups: ['validation:site_user_privilege:create',])]
    private Site $site;

    #[ORM\Column(type: 'integer')]
    #[Groups([
        'site_user_privilege:read',
        'site_user_privilege:create',
        'site_user_privilege:update',
    ])]
    #[Assert\NotBlank(groups: [
        'validation:site_user_privilege:create',
        'validation:site_user_privilege:update',
    ])]
    #[Assert\PositiveOrZero(groups: [
        'validation:site_user_privilege:create',
        'validation:site_user_privilege:update',
    ])]
    private int $privilege = 0;

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function setId(Uuid $id): SiteUserPrivilege
    {
        $this->id = $id;

        return $this;
    }

    public function hasUser(): bool
    {
        return isset($this->user);
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): SiteUserPrivilege
    {
        $this->user = $user;

        return $this;
    }

    public function hasSite(): bool
    {
        return isset($this->site);
    }

    public function getSite(): Site
    {
        return $this->site;
    }

    public function setSite(Site $site): SiteUserPrivilege
    {
        $this->site = $site;

        return $this;
    }

    public function getPrivilege(): int
    {
        return $this->privilege;
    }

    public function setPrivilege(int $privilege): SiteUserPrivilege
    {
        $this->privilege = $privilege;

        return $this;
    }
}
