<?php

namespace App\Service\Validator\SameSite;

use App\Entity\Data\Site;

interface JoinResourceBelongToSameSiteInterface
{
    public function supports(object $object): bool;

    public function __invoke(object $object): Site|false;
}
