<?php

namespace App\Validator;

use App\Entity\Vocabulary\Zoo\Bone;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class IsValidToothElementValidator extends ConstraintValidator
{
    private const array ALLOWED_CODES = ['MAX', 'N'];

    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof IsValidToothElement) {
            throw new UnexpectedTypeException($constraint, IsValidToothElement::class);
        }

        // Allow null values
        if (null === $value) {
            return;
        }

        // Check if value is instance of Bone
        if (!$value instanceof Bone) {
            $this->context->buildViolation($constraint->typeMessage)
                ->setParameter('{{ type }}', get_debug_type($value))
                ->addViolation();

            return;
        }

        // Check if bone code is in allowed values
        $code = $value->getCode();
        if (!in_array($code, self::ALLOWED_CODES, true)) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ code }}', $code)
                ->addViolation();
        }
    }
}
