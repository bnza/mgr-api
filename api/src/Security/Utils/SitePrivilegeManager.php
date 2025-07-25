<?php

namespace App\Security\Utils;

use App\Entity\Auth\SiteUserPrivilege;
use App\Entity\Auth\User;
use App\Entity\Data\Site;

class SitePrivilegeManager
{
    public function hasSitePrivileges(User $user, Site $site, ?SitePrivileges $privilege = SitePrivileges::User): bool
    {
        return $this->hasPrivilege(
            $user->getSitePrivilege($site),
            $privilege
        );
    }

    public function hasPrivilege(int|SiteUserPrivilege|null $privileges, ?SitePrivileges $privilege = SitePrivileges::User): bool
    {
        if (is_null($privileges)) {
            return false;
        }

        // Since $privileges is set, meaning the user has privileges on site,
        // checking positively against the lowest (SitePrivileges::User) returns early.
        // Also, SitePrivileges::User is 0 that always returns false in & bitwise operation
        if (SitePrivileges::User === $privilege) {
            return true;
        }

        if ($privileges instanceof SiteUserPrivilege) {
            $privileges = $privileges->getPrivilege();
        }

        return (bool) $privileges & $privilege->value;
    }

    public function grantPrivilege(int|SiteUserPrivilege $privileges, SitePrivileges $privilege): int
    {
        $privilegesValue = $privileges instanceof SiteUserPrivilege ? $privileges->getPrivilege() : $privileges;
        $privilegesValue |= $privilege->value;
        if ($privileges instanceof SiteUserPrivilege) {
            $privileges->setPrivilege($privilegesValue);
        }

        return $privilegesValue;
    }

    public function revokePrivilege(int|SiteUserPrivilege $privileges, SitePrivileges $privilege): int
    {
        $privilegesValue = $privileges instanceof SiteUserPrivilege ? $privileges->getPrivilege() : $privileges;
        $privilegesValue = $privilegesValue & ~$privilege->value;
        if ($privileges instanceof SiteUserPrivilege) {
            $privileges->setPrivilege($privilegesValue);
        }

        return $privilegesValue;
    }
}
