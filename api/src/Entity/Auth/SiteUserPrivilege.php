<?php
declare(strict_types=1);

namespace App\Entity\Auth;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use App\Entity\Data\Site;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Table;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\IdGenerator\UuidGenerator;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Uid\Uuid;

#[Entity]
#[Table(name: 'site_user_privileges', schema: 'auth')]
#[ORM\UniqueConstraint(columns: ['user_id', 'site_id'])]
#[ApiResource(
    operations: [
        new Get(),
        new GetCollection(),
    ],
    normalizationContext: ['groups' => ['site_user_privilege:read']],
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
    #[Groups(['site_user_privilege:read'])]
    private User $user;

    #[ORM\ManyToOne(targetEntity: Site::class, inversedBy: 'userPrivileges')]
    #[ORM\JoinColumn(name: 'site_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    #[Groups(['site_user_privilege:read'])]
    private Site $site;

    #[ORM\Column(type: 'integer')]
    #[Groups(['site_user_privilege:read'])]
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

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): SiteUserPrivilege
    {
        $this->user = $user;

        return $this;
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
