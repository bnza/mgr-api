<?php

declare(strict_types=1);

namespace App\Security\Voter;

use App\Entity\Auth\User;
use App\Entity\Data\SamplingSite;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class SamplingSiteVoter extends Voter
{
    use ApiOperationVoterTrait;

    public function __construct(
        private readonly AccessDecisionManagerInterface $accessDecisionManager,
    ) {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return $this->isAttributeSupported($attribute)
            && $subject instanceof SamplingSite;
    }

    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        if (self::READ === $attribute) {
            return true;
        }

        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        if ($this->accessDecisionManager->decide($token, ['ROLE_ADMIN'])) {
            return true;
        }

        $hasRoleEditor = $this->accessDecisionManager->decide($token, ['ROLE_EDITOR']);
        $hasRoleGeoArchaeologist = $this->accessDecisionManager->decide($token, ['ROLE_GEO_ARCHAEOLOGIST']);

        return $hasRoleEditor && $hasRoleGeoArchaeologist;
    }
}
