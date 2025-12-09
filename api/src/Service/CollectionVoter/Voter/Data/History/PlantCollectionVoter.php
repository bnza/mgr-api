<?php

namespace App\Service\CollectionVoter\Voter\Data\History;

use App\Service\CollectionVoter\Voter\AbstractCollectionVoter;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

readonly class PlantCollectionVoter extends AbstractCollectionVoter
{
    protected function voteOnSubCollection(object $parent, TokenInterface $token): bool
    {
        return $this->accessDecisionManager->decide($token, ['ROLE_HISTORIAN']);
    }

    protected function voteOnWholeCollection(string $context, TokenInterface $token): bool
    {
        return $this->accessDecisionManager->decide($token, ['ROLE_HISTORIAN']);
    }
}
