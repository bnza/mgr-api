<?php

namespace App\Security;

use Symfony\Component\Security\Core\User\UserInterface;

interface RoleProviderInterface
{
    public function getValidRoles(): array;

    public function isValidRole(string $role): bool;

    public function hasSpecialistRole(?UserInterface $user): bool;

    public function getSpecialistRoles(): array;

    public function getBaseRoles(): array;
}
