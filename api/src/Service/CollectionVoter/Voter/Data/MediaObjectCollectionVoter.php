<?php

namespace App\Service\CollectionVoter\Voter\Data;

use App\Service\CollectionVoter\Voter\AbstractCollectionVoter;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

readonly class MediaObjectCollectionVoter extends AbstractCollectionVoter
{
    protected function voteOnSubCollection(object $parent, TokenInterface $token): bool
    {
        return false;
    }

    protected function voteOnWholeCollection(string $context, TokenInterface $token): bool
    {
        return $this->accessDecisionManager->decide($token, ['IS_AUTHENTICATED_FULLY']);
    }
}
