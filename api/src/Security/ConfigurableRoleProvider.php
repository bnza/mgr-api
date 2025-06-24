<?php

namespace App\Security;

use Symfony\Component\DependencyInjection\Attribute\Autowire;

readonly class ConfigurableRoleProvider implements RoleProviderInterface
{
    public function __construct(
        #[Autowire('%app.roles%')] private array $roles
    ) {
    }

    public function getValidRoles(): array
    {
        return $this->roles;
    }

    public function isValidRole(string $role): bool
    {
        return in_array($role, $this->roles, true);
    }
}
