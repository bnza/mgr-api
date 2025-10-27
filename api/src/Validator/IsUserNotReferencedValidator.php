<?php

declare(strict_types=1);

namespace App\Validator;

use App\Entity\Auth\User;
use App\Repository\UserRepository;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class IsUserNotReferencedValidator extends ConstraintValidator
{
    public function __construct(private readonly UserRepository $userRepository)
    {
    }

    /**
     * @param User|null $value
     */
    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof IsUserNotReferenced) {
            // Wrong constraint used; ignore gracefully
            return;
        }

        if (!$value instanceof User) {
            return;
        }

        $classes = $this->userRepository->getReferencingEntityClasses($value);
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
