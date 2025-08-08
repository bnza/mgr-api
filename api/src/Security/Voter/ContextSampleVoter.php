<?php

namespace App\Security\Voter;

use App\Entity\Auth\User;
use App\Entity\Data\Join\ContextSample;
use App\Security\Utils\SitePrivilegeManager;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class ContextSampleVoter extends Voter
{
    use ApiOperationVoterTrait;

    public function __construct(
        private readonly AccessDecisionManagerInterface $accessDecisionManager,
        private readonly SitePrivilegeManager $sitePrivilegeManager,
    ) {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return $this->isAttributeSupported($attribute)
            && $subject instanceof ContextSample;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        if (self::READ === $attribute) {
            return true;
        }

        if ($this->accessDecisionManager->decide($token, ['ROLE_ADMIN'])) {
            return true;
        }

        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        return $this->sitePrivilegeManager->hasSitePrivileges($user, $subject->getContext()->getSite());
    }
}
