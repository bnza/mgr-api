<?php

declare(strict_types=1);

namespace App\Validator;

use App\Entity\Data\StratigraphicUnit;
use App\Repository\StratigraphicUnitRepository;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class IsStratigraphicUnitNotReferencedValidator extends ConstraintValidator
{
    public function __construct(private readonly StratigraphicUnitRepository $stratigraphicUnitRepository)
    {
    }

    /**
     * @param StratigraphicUnit|null $value
     */
    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof IsStratigraphicUnitNotReferenced) {
            // Wrong constraint used; ignore gracefully
            return;
        }

        if (!$value instanceof StratigraphicUnit) {
            return;
        }

        $classes = $this->stratigraphicUnitRepository->getReferencingEntityClasses($value);
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
