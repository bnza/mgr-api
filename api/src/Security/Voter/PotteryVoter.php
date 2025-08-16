<?php

namespace App\Security\Voter;

use App\Entity\Auth\User;
use App\Entity\Data\Pottery;
use App\Security\Utils\SitePrivilegeManager;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class PotteryVoter extends Voter
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
            && $subject instanceof Pottery;
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

        if (!$this->accessDecisionManager->decide($token, ['ROLE_CERAMIC_SPECIALIST'])) {
            return false;
        }

        return match ($attribute) {
            self::CREATE => $this->accessDecisionManager->decide($token, ['is_granted("create", object)'], $subject->getStratigraphicUnit()),
            self::UPDATE => $this->accessDecisionManager->decide($token, ['is_granted("update", object)'], $subject->getStratigraphicUnit()),
            self::DELETE => $this->accessDecisionManager->decide($token, ['is_granted("delete", object)'], $subject->getStratigraphicUnit()),
            default => throw new \LogicException("Unsupported voter attribute: '$attribute'"),
        };
    }
}
