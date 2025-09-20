<?php

namespace App\Security;

use Symfony\Component\DependencyInjection\Attribute\Autowire;

readonly class ConfigurableRoleProvider implements RoleProviderInterface
{
    private array $roles;

    public function __construct(
        #[Autowire('%app.base_roles%')] private array $baseRoles,
        #[Autowire('%app.specialist_roles%')] private array $specialistRoles,
    ) {
        $this->roles = array_merge($baseRoles, $specialistRoles);
    }

    public function getValidRoles(): array
    {
        return $this->roles;
    }

    public function isValidRole(string $role): bool
    {
        return in_array($role, $this->roles, true);
    }

    public function hasSpecialistRole(array $roles): bool
    {
        return (bool) array_intersect($roles, $this->specialistRoles);
    }
}
