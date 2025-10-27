<?php

declare(strict_types=1);

namespace App\Validator;

use Symfony\Component\Validator\Constraint;

#[\Attribute(\Attribute::TARGET_CLASS)]
class IsUserNotReferenced extends Constraint
{
    /**
     * Default error message. The placeholder {{ classes }} will be replaced
     * with a comma-separated list of short class names that still reference the user.
     */
    public string $message = 'Cannot delete the user because it is referenced by: {{ classes }}.';

    public function getTargets(): string|array
    {
        return self::CLASS_CONSTRAINT;
    }

    public function validatedBy(): string
    {
        // Resolves to IsUserNotReferencedValidator
        return static::class.'Validator';
    }
}
