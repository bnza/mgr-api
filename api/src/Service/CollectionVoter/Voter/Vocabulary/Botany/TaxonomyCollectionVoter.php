<?php

namespace App\Service\CollectionVoter\Voter\Vocabulary\Botany;

use App\Service\CollectionVoter\Voter\AbstractCollectionVoter;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

readonly class TaxonomyCollectionVoter extends AbstractCollectionVoter
{
    protected function voteOnSubCollection(object $parent, TokenInterface $token): bool
    {
        return false;
    }

    /**
     * Only administrators can create new users.
     */
    protected function voteOnWholeCollection(string $context, TokenInterface $token): bool
    {
        return $this->accessDecisionManager->decide($token, ['ROLE_ARCHAEOBOTANIST'])
            && $this->accessDecisionManager->decide($token, ['ROLE_EDITOR']);
    }
}
