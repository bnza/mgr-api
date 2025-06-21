<?php

namespace App\Entity\Auth;

use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Table;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\IdGenerator\UuidGenerator;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Uid\Uuid;

#[Entity]
#[Table(name: 'users', schema: 'auth')]
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
    public string $email;

    #[ORM\Column]
    private string $password;

    public ?string $plainPassword = null;

    #[ORM\Column(type: 'simple_array')]
    private array $roles = ['ROLE_USER'];

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

    public function getId(): ?Uuid
    {
        return $this->id;
    }

    public function getUserIdentifier(): string
    {
        return $this->email;
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

    public function eraseCredentials(): void
    {
        $this->plainPassword = null;
    }
}
