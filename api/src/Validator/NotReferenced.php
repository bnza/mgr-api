<?php

declare(strict_types=1);

namespace App\Validator;

use Symfony\Component\Validator\Constraint;

#[\Attribute(\Attribute::TARGET_CLASS)]
class NotReferenced extends Constraint
{
    public function __construct(
        public readonly string $class,
        public string $message = 'Cannot delete the resource because it is referenced by: {{ classes }}.',
        ?array $groups = null,
        mixed $payload = null,
    ) {
        parent::__construct(groups: $groups, payload: $payload);
    }

    public function getTargets(): string|array
    {
        return self::CLASS_CONSTRAINT;
    }

    public function validatedBy(): string
    {
        return NotReferencedValidator::class;
    }
}
