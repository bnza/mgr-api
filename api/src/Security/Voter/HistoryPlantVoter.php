<?php

namespace App\Security\Voter;

use App\Entity\Data\History\Plant;
use App\Security\Utils\SitePrivilegeManager;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class HistoryPlantVoter extends Voter
{
    use ApiOperationVoterTrait;

    public function __construct(
        private readonly AccessDecisionManagerInterface $accessDecisionManager,
        private readonly SitePrivilegeManager $sitePrivilegeManager,
        private readonly Security $security,
    ) {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return $this->isAttributeSupported($attribute) && $subject instanceof Plant;
    }

    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        if (self::READ === $attribute) {
            return true;
        }

        return $this->accessDecisionManager->decide($token, ['ROLE_ADMIN'])
            || $this->accessDecisionManager->decide($token, ['ROLE_HISTORIAN']);
    }
}
