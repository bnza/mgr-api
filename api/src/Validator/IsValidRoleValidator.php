<?php

namespace App\Validator;

use App\Security\RoleProviderInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

class IsValidRoleValidator extends ConstraintValidator
{
    public function __construct(readonly RoleProviderInterface $roleProvider)
    {
    }

    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof IsValidRole) {
            throw new UnexpectedTypeException($constraint, IsValidRole::class);
        }

        if (null === $value || '' === $value) {
            return;
        }

        if (!is_string($value)) {
            throw new UnexpectedValueException($value, 'string');
        }

        if (!$this->roleProvider->isValidRole($value)) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ string }}', $value)
                ->addViolation();
        }
    }
}
