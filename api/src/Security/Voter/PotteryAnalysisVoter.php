<?php

namespace App\Security\Voter;

use App\Entity\Auth\User;
use App\Entity\Data\Join\PotteryAnalysis;
use App\Security\Utils\SitePrivilegeManager;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class PotteryAnalysisVoter extends Voter
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
        return $this->isAttributeSupported($attribute)
            && $subject instanceof PotteryAnalysis;
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

        return $this->security->isGranted($attribute, $subject->getPottery());
    }
}
