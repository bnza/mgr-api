<?php

namespace App\Service\CollectionVoter\Voter\Data;

use App\Entity\Auth\User;
use App\Entity\Data\StratigraphicUnit;
use App\Service\CollectionVoter\Voter\AbstractCollectionVoter;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

readonly class PotteryCollectionVoter extends AbstractCollectionVoter
{
    protected function voteOnSubCollection(object $parent, TokenInterface $token): bool
    {
        $user = $token->getUser();
        if ($parent instanceof StratigraphicUnit && $user instanceof User) {
            return $this->accessDecisionManager->decide($token, ['ROLE_CERAMIC_SPECIALIST'])
                && $this->sitePrivilegeManager->hasSitePrivileges($user, $parent->getSite());
        }

        return false;
    }

    protected function voteOnWholeCollection(string $context, TokenInterface $token): bool
    {
        return $this->accessDecisionManager->decide($token, ['ROLE_CERAMIC_SPECIALIST']);
    }
}
