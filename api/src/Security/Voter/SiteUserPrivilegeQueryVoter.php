<?php

declare(strict_types=1);

namespace App\Security\Voter;

use App\Entity\Auth\SiteUserPrivilege;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class SiteUserPrivilegeQueryVoter extends Voter
{
    public const string READ = 'read';

    public function __construct(
        private readonly AccessDecisionManagerInterface $accessDecisionManager,
    ) {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return self::READ === $attribute
            && $subject instanceof SiteUserPrivilege;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        return $this->accessDecisionManager->decide($token, ['ROLE_ADMIN'])
            || $this->accessDecisionManager->decide($token, ['ROLE_EDITOR']);
    }
}
