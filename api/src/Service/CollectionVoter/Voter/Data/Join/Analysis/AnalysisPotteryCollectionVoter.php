<?php

namespace App\Service\CollectionVoter\Voter\Data\Join\Analysis;

use App\Entity\Auth\User;
use App\Entity\Data\Pottery;
use App\Service\CollectionVoter\Voter\AbstractCollectionVoter;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

readonly class AnalysisPotteryCollectionVoter extends AbstractCollectionVoter
{
    protected function voteOnSubCollection(object $parent, TokenInterface $token): bool
    {
        $user = $token->getUser();
        if ($parent instanceof Pottery && $user instanceof User) {
            return $this->accessDecisionManager->decide($token, ['ROLE_CERAMIC_SPECIALIST'])
                && $this->sitePrivilegeManager->hasSitePrivileges($user, $parent->getStratigraphicUnit()->getSite());
        }

        return false;
    }

    /**
     * Any specialist can potentially create a new entry, stricter checking will happen when the actual POST operation is performed.
     */
    protected function voteOnWholeCollection(string $context, TokenInterface $token): bool
    {
        return $this->roleProvider->hasSpecialistRole($token->getUser());
    }
}
