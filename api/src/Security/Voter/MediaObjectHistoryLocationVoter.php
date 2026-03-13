<?php

declare(strict_types=1);

namespace App\Security\Voter;

use App\Entity\Data\Join\MediaObject\MediaObjectHistoryLocation;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class MediaObjectHistoryLocationVoter extends Voter
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
            && $subject instanceof MediaObjectHistoryLocation;
    }

    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        if (self::READ === $attribute) {
            return $this->accessDecisionManager->decide($token, ['IS_AUTHENTICATED_FULLY']);
        }

        if (self::CREATE === $attribute) {
            return $this->accessDecisionManager->decide($token, ['ROLE_ADMIN'])
                || $this->accessDecisionManager->decide($token, ['ROLE_HISTORIAN']);
        }

        return $this->security->isGranted($attribute, $subject->getMediaObject())
            || $this->security->isGranted($attribute, $subject->getItem());
    }
}
