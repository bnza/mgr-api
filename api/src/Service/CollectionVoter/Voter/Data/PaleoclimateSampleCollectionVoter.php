<?php

namespace App\Service\CollectionVoter\Voter\Data;

use App\Service\CollectionVoter\Voter\AbstractCollectionVoter;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

readonly class PaleoclimateSampleCollectionVoter extends AbstractCollectionVoter
{
    protected function voteOnSubCollection(object $parent, TokenInterface $token): bool
    {
        return $this->voteOnWholeCollection('', $token);
    }

    protected function voteOnWholeCollection(string $context, TokenInterface $token): bool
    {
        if ($this->accessDecisionManager->decide($token, ['ROLE_ADMIN'])) {
            return true;
        }

        return $this->accessDecisionManager->decide($token, ['ROLE_PALEOCLIMATOLOGIST']);
    }
}
