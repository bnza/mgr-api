<?php
declare(strict_types=1);

namespace App\Security\Voter;

use App\Entity\Auth\User;
use App\Entity\Data\Site;
use App\Security\Utils\SitePrivileges;
use App\Security\Utils\SitePrivilegeManager;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class SiteVoter extends Voter
{

    public const string CREATE = 'create';
    public const string READ = 'read';
    public const string UPDATE = 'update';
    public const string DELETE = 'delete';

    public function __construct(
        private readonly AccessDecisionManagerInterface $accessDecisionManager,
        private readonly SitePrivilegeManager $sitePrivilegeManager,
    ) {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array(
                $attribute,
                [
                    self::CREATE,
                    self::READ,
                    self::UPDATE,
                    self::DELETE,
                ]
            )
            && $subject instanceof Site;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        if ($attribute === self::READ) {
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

        if (!$this->accessDecisionManager->decide($token, ['ROLE_EDITOR'])) {
            return false;
        }

        return match ($attribute) {
            self::CREATE => true,
            self::UPDATE, self::DELETE => $this->hasSiteEditorPrivileges($user, $site),
            default => throw new \LogicException("Unsupported voter attribute: '$attribute'")
        };
    }

    private function hasSiteEditorPrivileges(User $user, Site $site): bool
    {
        return $site->getCreatedBy()->getId() === $user->getId()
            || $this->sitePrivilegeManager->hasPrivilege(
                $user->getSitePrivilege($site),
                SitePrivileges::Editor
            );
    }
}
