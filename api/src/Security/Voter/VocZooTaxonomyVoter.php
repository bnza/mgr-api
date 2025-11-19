<?php

namespace App\Security\Voter;

use App\Entity\Vocabulary\Zoo\Taxonomy;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class VocZooTaxonomyVoter extends Voter
{
    use ApiOperationVoterTrait;

    public function __construct(
        private readonly AccessDecisionManagerInterface $accessDecisionManager,
    ) {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return $this->isAttributeSupported($attribute)
            && $subject instanceof Taxonomy;
    }

    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        if (self::READ === $attribute) {
            return true;
        }

        if ($this->accessDecisionManager->decide($token, ['ROLE_ADMIN'])) {
            return true;
        }

        return $this->accessDecisionManager->decide($token, ['ROLE_EDITOR']) && $this->accessDecisionManager->decide($token, ['ROLE_ZOO_ARCHAEOLOGIST']);
    }
}
