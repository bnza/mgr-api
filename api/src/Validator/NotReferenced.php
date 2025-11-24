<?php

declare(strict_types=1);

namespace App\Validator;

use Symfony\Component\Validator\Constraint;

#[\Attribute(\Attribute::TARGET_CLASS)]
class NotReferenced extends Constraint
{
    /**
     * Default error message. The placeholder {{ classes }} will be replaced
     * with a comma-separated list of short class names that still reference the entity.
     */
    public string $message = 'Cannot delete the resource because it is referenced by: {{ classes }}.';

    /**
     *  The class (FQCN) of the entity for which the repository must expose
     *  `getReferencingEntityClasses(object $subject): array`.
     *
     * @param class-string $class
     */
    public function __construct(
        public readonly string $class,
        ?string $message = null,
        ?array $groups = null,
        mixed $payload = null,
    ) {
        parent::__construct([], $groups, $payload);
        if (null !== $message) {
            $this->message = $message;
        }
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
