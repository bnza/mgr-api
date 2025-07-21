<?php

namespace App\Security\Utils;

enum SitePrivileges: int
{
    case User = 0b0;
    case Editor = 0b1;
}
