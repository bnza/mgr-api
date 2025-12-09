<?php

namespace App\Service\CollectionVoter\Voter\Data\Join\Analysis;

use App\Entity\Auth\User;
use App\Entity\Data\Context;
use App\Service\CollectionVoter\Voter\AbstractCollectionVoter;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

readonly class AnalysisContextZooCollectionVoter extends AbstractCollectionVoter
{
    protected function voteOnSubCollection(object $parent, TokenInterface $token): bool
    {
        $user = $token->getUser();
        if ($parent instanceof Context && $user instanceof User) {
            return $this->accessDecisionManager->decide($token, ['ROLE_ZOO_ARCHAEOLOGIST'])
                && $this->sitePrivilegeManager->hasSitePrivileges($user, $parent->getSite());
        }

        return false;
    }

    /**
     * Any specialist can potentially create a new entry, stricter checking will happen when the actual POST operation is performed.
     */
    protected function voteOnWholeCollection(string $context, TokenInterface $token): bool
    {
        return $this->accessDecisionManager->decide($token, ['ROLE_ZOO_ARCHAEOLOGIST']);
    }
}
