<?php

namespace App\Security\Voter;

use App\Entity\Data\Analysis;
use App\Security\RoleProviderInterface;
use App\Security\Utils\SitePrivilegeManager;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class AnalysisVoter extends Voter
{
    use ApiOperationVoterTrait;

    public function __construct(
        private readonly AccessDecisionManagerInterface $accessDecisionManager,
        private readonly SitePrivilegeManager $sitePrivilegeManager,
        private readonly Security $security,
        private readonly RoleProviderInterface $roleProvider,
    ) {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return $this->isAttributeSupported($attribute)
            && $subject instanceof Analysis;
    }

    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        if (self::READ === $attribute) {
            return true;
        }

        $user = $token->getUser();

        if ($this->accessDecisionManager->decide($token, ['ROLE_ADMIN'])) {
            return true;
        }

        /* @var Analysis $subject */
        return match ($attribute) {
            self::CREATE => $this->roleProvider->hasSpecialistRole($user),
            self::UPDATE, self::DELETE => $user?->getId() === $subject->getCreatedBy()->getId(),
            default => throw new \LogicException("Unsupported voter attribute: '$attribute'"),
        };
    }
}
