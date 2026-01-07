<?php

declare(strict_types=1);

namespace App\Security\Voter;

use App\Entity\Data\Join\MediaObject\BaseMediaObjectJoin;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class MediaObjectJoinVoter extends Voter
{
    use ApiOperationVoterTrait;

    public function __construct(
        private readonly AccessDecisionManagerInterface $accessDecisionManager,
        private readonly Security $security,
    ) {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return $this->isAttributeSupported($attribute)
            && $subject instanceof BaseMediaObjectJoin;
    }

    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        if (self::READ === $attribute) {
            return $this->accessDecisionManager->decide($token, ['IS_AUTHENTICATED_FULLY']);
        }

        return $this->security->isGranted(self::UPDATE, $subject->getItem());
    }
}
