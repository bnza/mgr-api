<?php

namespace App\Service\CollectionVoter\Voter\Data;

use App\Service\CollectionVoter\Voter\AbstractCollectionVoter;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

readonly class SamplingSiteCollectionVoter extends AbstractCollectionVoter
{
    protected function voteOnSubCollection(object $parent, TokenInterface $token): bool
    {
        return false;
    }

    protected function voteOnWholeCollection(string $context, TokenInterface $token): bool
    {
        if ($this->accessDecisionManager->decide($token, ['ROLE_ADMIN'])) {
            return true;
        }

        $hasRoleEditor = $this->accessDecisionManager->decide($token, ['ROLE_EDITOR']);
        $hasRoleGeoArchaeologist = $this->accessDecisionManager->decide($token, ['ROLE_GEO_ARCHAEOLOGIST']);

        return $hasRoleEditor && $hasRoleGeoArchaeologist;
    }
}
