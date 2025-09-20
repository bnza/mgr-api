<?php

namespace App\Security;

interface RoleProviderInterface
{
    public function getValidRoles(): array;

    public function isValidRole(string $role): bool;

    public function hasSpecialistRole(array $roles): bool;
}
