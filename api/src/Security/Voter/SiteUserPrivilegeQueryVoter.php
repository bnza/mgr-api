<?php

declare(strict_types=1);

namespace App\Security\Voter;

use App\Entity\Auth\SiteUserPrivilege;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class SiteUserPrivilegeQueryVoter extends Voter
{
    use ApiOperationVoterTrait;

    public function __construct(
        private readonly AccessDecisionManagerInterface $accessDecisionManager,
    ) {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return self::READ === $attribute
            && $subject instanceof SiteUserPrivilege;
    }

    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        return $this->accessDecisionManager->decide($token, ['ROLE_ADMIN'])
            || $this->accessDecisionManager->decide($token, ['ROLE_EDITOR']);
    }
}
