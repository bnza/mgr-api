<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraint;

#[\Attribute(\Attribute::TARGET_CLASS)]
class BelongToTheSameSite extends Constraint
{
    public string $message = 'All related entities must belong to the same site.';

    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }
}
