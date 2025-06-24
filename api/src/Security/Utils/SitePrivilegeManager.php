<?php

namespace App\Security\Utils;

use App\Entity\Auth\SiteUserPrivilege;

class SitePrivilegeManager
{
    public function hasPrivilege(int|SiteUserPrivilege|null $privileges, SitePrivileges $privilege): bool
    {
        if ($privileges === null) {
            return false;
        }

        if ($privileges instanceof SiteUserPrivilege) {
            $privileges = $privileges->getPrivilege();
        }

        return (bool)$privileges & $privilege->value;
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
