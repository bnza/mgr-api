<?php

namespace App\Validator;

use App\Service\Validator\SameSite\JoinResourceBelongToSameSiteInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

class BelongToTheSameSiteValidator extends ConstraintValidator
{
    /**
     * @param iterable<JoinResourceBelongToSameSiteInterface> $strategies
     */
    public function __construct(
        private readonly iterable $strategies,
    ) {
    }

    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof BelongToTheSameSite) {
            throw new UnexpectedTypeException($constraint, BelongToTheSameSite::class);
        }

        if (null === $value) {
            return;
        }

        $strategy = $this->findStrategy($value);

        if (null === $strategy) {
            throw new UnexpectedValueException($value, 'supported join resource entity');
        }

        $result = $strategy($value);

        if (false === $result) {
            $this->context->buildViolation($constraint->message)
                ->addViolation();
        }
    }

    private function findStrategy(object $object): ?JoinResourceBelongToSameSiteInterface
    {
        foreach ($this->strategies as $strategy) {
            if ($strategy->supports($object)) {
                return $strategy;
            }
        }

        return null;
    }
}
