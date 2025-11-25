<?php

namespace App\Validator;

use App\Entity\Data\Analysis as AnalysisEntity;
use App\Entity\Data\Join\Analysis\BaseAnalysisJoin;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class PermittedAnalysisTypeValidator extends ConstraintValidator
{
    /**
     * @param AnalysisEntity|null $value
     */
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof PermittedAnalysisType) {
            return; // unsupported constraint
        }

        if (null === $value) {
            // NotBlank handles nulls when required; this validator focuses on type allowance
            return;
        }

        if (!$value instanceof AnalysisEntity) {
            // If something else is passed, do not validate here
            return;
        }

        $object = $this->context->getObject();
        if (!\is_object($object) && !($object instanceof BaseAnalysisJoin)) {
            return;
        }

        $code = $value->getType()->code;

        /** @var string[] $allowed */
        $allowed = $object::getPermittedAnalysisTypes();

        if (!\in_array($code, $allowed, true)) {
            $this->context
                ->buildViolation($constraint->message)
                ->setParameter('{{ code }}', (string) $code)
                ->setParameter('{{ class }}', $object::class)
                ->setParameter('{{ allowed }}', implode(', ', $allowed))
                ->addViolation();
        }
    }
}
