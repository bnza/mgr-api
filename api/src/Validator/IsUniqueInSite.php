<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraint;

#[\Attribute(\Attribute::TARGET_CLASS)]
class IsUniqueInSite extends Constraint
{
    public string $message = 'The identifier must be unique within the same site.';

    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }
}
