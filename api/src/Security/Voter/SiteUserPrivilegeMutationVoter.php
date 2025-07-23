<?php

declare(strict_types=1);

namespace App\Security\Voter;

use App\Entity\Auth\SiteUserPrivilege;
use App\Entity\Auth\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class SiteUserPrivilegeMutationVoter extends Voter
{
    public const string CREATE = 'create';
    public const string UPDATE = 'update';
    public const string DELETE = 'delete';

    public function __construct(
        private readonly AccessDecisionManagerInterface $accessDecisionManager,
    ) {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array(
            $attribute,
            [
                self::CREATE,
                self::UPDATE,
                self::DELETE,
            ]
        )
            && $subject instanceof SiteUserPrivilege;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        /** @var SiteUserPrivilege $siteUserPrivilege */
        $siteUserPrivilege = $subject;

        if (!$user instanceof User) {
            return false;
        }

        if (!$user->getId() === $siteUserPrivilege->getUser()?->getId()) {
            return false;
        }

        if ($this->accessDecisionManager->decide($token, ['ROLE_ADMIN'])) {
            return true;
        }

        if (!$this->accessDecisionManager->decide($token, ['ROLE_EDITOR'])) {
            return false;
        }

        // If site property is not set in the request body, validation will eventually fail
        if (self::CREATE === $attribute && !$siteUserPrivilege->hasSite()) {
            return true;
        }

        return $siteUserPrivilege->getSite()->getCreatedBy()->getId() === $user->getId();
    }
}
