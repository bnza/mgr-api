<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserInterface;

readonly class CurrentUserProvider implements ProviderInterface
{
    public function __construct(private Security $security)
    {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): UserInterface
    {
        $user = $this->security->getUser();

        if (!$user) {
            throw new AuthenticationException('Authentication required to access current user information.');
        }

        // Returns a fake user if an anonymous request, security check happens after
        return $user;
    }
}
