<?php

declare(strict_types=1);

namespace App\Validator;

use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class NotReferencedValidator extends ConstraintValidator
{
    public function __construct(private readonly ManagerRegistry $registry)
    {
    }

    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof NotReferenced) {
            return;
        }

        if (!\is_object($value) || !$value instanceof $constraint->class) {
            // Not the target entity instance; ignore.
            return;
        }

        $repository = $this->registry->getRepository($constraint->class);

        if (!\is_object($repository) || !method_exists($repository, 'getReferencingEntityClasses')) {
            // Repository does not support the required method; treat as no references.
            return;
        }

        /** @var array<class-string> $classes */
        $classes = $repository->getReferencingEntityClasses($value);
        if (empty($classes)) {
            return;
        }

        $shortNames = array_map(static function (string $fqcn): string {
            $pos = strrpos($fqcn, '\\');

            return false === $pos ? $fqcn : substr($fqcn, $pos + 1);
        }, $classes);

        $this->context
            ->buildViolation($constraint->message)
            ->setParameter('{{ classes }}', implode(', ', $shortNames))
            ->addViolation();
    }
}
