<?php

declare(strict_types=1);

namespace App\Security\Voter;

use App\Entity\Auth\User;
use App\Entity\Data\Site;
use App\Security\Utils\SitePrivilegeManager;
use App\Security\Utils\SitePrivileges;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class SiteVoter extends Voter
{
    use ApiOperationVoterTrait;

    public function __construct(
        private readonly AccessDecisionManagerInterface $accessDecisionManager,
        private readonly SitePrivilegeManager $sitePrivilegeManager,
    ) {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return $this->isAttributeSupported($attribute)
            && $subject instanceof Site;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        if (self::READ === $attribute) {
            return true;
        }

        $user = $token->getUser();
        /** @var Site $site */
        $site = $subject;

        if (!$user instanceof User) {
            return false;
        }

        if ($this->accessDecisionManager->decide($token, ['ROLE_ADMIN'])) {
            return true;
        }

        $hasRoleEditor = $this->accessDecisionManager->decide($token, ['ROLE_EDITOR']);
        if (self::CREATE === $attribute) {
            return $hasRoleEditor;
        }

        $isSiteCreator = $this->isSiteCreator($user, $site);
        $hasSiteEditorPrivileges = $this->sitePrivilegeManager->hasSitePrivileges($user, $site, SitePrivileges::Editor);

        return match ($attribute) {
            self::UPDATE => $hasSiteEditorPrivileges,
            self::DELETE => $isSiteCreator && $hasRoleEditor && $hasSiteEditorPrivileges,
            default => throw new \LogicException("Unsupported voter attribute: '$attribute'"),
        };
    }

    private function isSiteCreator(User $user, Site $site): bool
    {
        return $site->getCreatedBy()?->getId() === $user->getId();
    }
}
