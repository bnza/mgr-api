<?php

declare(strict_types=1);

namespace App\Security\Voter;

use App\Entity\Data\MediaObject;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class MediaObjectVoter extends Voter
{
    use ApiOperationVoterTrait;

    public function __construct(
        private readonly AccessDecisionManagerInterface $accessDecisionManager,
    ) {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return $this->isAttributeSupported($attribute)
            && $subject instanceof MediaObject;
    }

    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        $isAuthenticated = $this->accessDecisionManager->decide($token, ['IS_AUTHENTICATED_FULLY']);
        $isAdmin = $this->accessDecisionManager->decide($token, ['ROLE_ADMIN']);
        $isCurrentUser = $token->getUser() === $subject->getUploadedBy();

        return match ($attribute) {
            self::CREATE, self::READ => $isAuthenticated,
            self::UPDATE, self::DELETE => $isAdmin || $isCurrentUser,
            default => throw new \LogicException("Unsupported voter attribute: '$attribute'"),
        };
    }
}
