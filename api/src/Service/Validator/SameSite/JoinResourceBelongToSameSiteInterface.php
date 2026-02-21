<?php

namespace App\Service\Validator\SameSite;

use App\Entity\Data\ArchaeologicalSite;

interface JoinResourceBelongToSameSiteInterface
{
    public function supports(object $object): bool;

    public function __invoke(object $object): ArchaeologicalSite|false;
}
