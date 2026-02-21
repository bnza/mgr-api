<?php

namespace App\Service\CollectionVoter\Voter\Data;

use App\Entity\Auth\User;
use App\Entity\Data\ArchaeologicalSite;
use App\Service\CollectionVoter\Voter\AbstractCollectionVoter;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

readonly class ContextCollectionVoter extends AbstractCollectionVoter
{
    protected function voteOnSubCollection(object $parent, TokenInterface $token): bool
    {
        $user = $token->getUser();
        if ($parent instanceof ArchaeologicalSite && $user instanceof User) {
            return $this->sitePrivilegeManager->hasSitePrivileges($user, $parent);
        }

        return false;
    }

    protected function voteOnWholeCollection(string $context, TokenInterface $token): bool
    {
        return $this->accessDecisionManager->decide($token, ['IS_AUTHENTICATED_FULLY']);
    }
}
