<?php

namespace App\Service\CollectionVoter\Voter\Data;

use App\Service\CollectionVoter\Voter\AbstractCollectionVoter;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

readonly class ArchaeologicalSiteCollectionVoter extends AbstractCollectionVoter
{
    protected function voteOnSubCollection(object $parent, TokenInterface $token): bool
    {
        return false;
    }

    /**
     * Any specialist can potentially create a new entry, stricter checking will happen when the actual POST operation is performed.
     */
    protected function voteOnWholeCollection(string $context, TokenInterface $token): bool
    {
        return $this->accessDecisionManager->decide($token, ['ROLE_EDITOR']);
    }
}
