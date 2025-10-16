<?php

declare(strict_types=1);

namespace App\Security;

use App\Entity\Auth\User;
use Symfony\Component\Security\Core\Exception\DisabledException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class UserChecker implements UserCheckerInterface
{
    public function checkPreAuth(UserInterface $user): void
    {
        if (!$user instanceof User) {
            return;
        }

        if (!$user->isEnabled()) {
            throw new DisabledException('User account is disabled.');
        }
    }

    public function checkPostAuth(UserInterface $user): void
    {
        // You can add additional post-authentication checks here if needed
    }
}
