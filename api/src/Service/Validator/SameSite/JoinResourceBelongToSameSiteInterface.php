<?php

namespace App\Service\Validator\SameSite;

interface JoinResourceBelongToSameSiteInterface
{
    public function supports(object $object): bool;

    public function __invoke(object $object): bool;
}
