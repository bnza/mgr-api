<?php

namespace App\Service\CollectionVoter\Voter\Data;

use App\Entity\Auth\User;
use App\Entity\Data\ArchaeologicalSite;
use App\Service\CollectionVoter\Voter\AbstractCollectionVoter;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

readonly class SiteUserPrivilegeCollectionVoter extends AbstractCollectionVoter
{
    protected function voteOnSubCollection(object $parent, TokenInterface $token): bool
    {
        $user = $token->getUser();
        if ($parent instanceof ArchaeologicalSite && $user instanceof User) {
            return $this->accessDecisionManager->decide($token, ['ROLE_EDITOR'])
                && $parent->getCreatedBy()->getId() === $user->getId();
        }

        return false;
    }

    /**
     * Only role admin can create new entries.
     */
    protected function voteOnWholeCollection(string $context, TokenInterface $token): bool
    {
        return false;
    }
}
