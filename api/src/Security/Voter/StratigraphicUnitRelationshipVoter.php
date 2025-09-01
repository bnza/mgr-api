<?php

declare(strict_types=1);

namespace App\Security\Voter;

use App\Entity\Data\View\StratigraphicUnitRelationshipView;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class StratigraphicUnitRelationshipVoter extends Voter
{
    use ApiOperationVoterTrait;

    public function __construct(
        private readonly AccessDecisionManagerInterface $accessDecisionManager,
        private readonly Security $security,
    ) {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return $this->isAttributeSupported($attribute)
            && $subject instanceof StratigraphicUnitRelationshipView;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        if (self::READ === $attribute) {
            return $this->accessDecisionManager->decide($token, ['IS_AUTHENTICATED_FULLY']);
        }

        $stratigraphicUnit = $subject->getLftStratigraphicUnit();

        if (!$stratigraphicUnit) {
            return true;
        }

        return $this->security->isGranted($attribute, $subject->getLftStratigraphicUnit());
    }
}
