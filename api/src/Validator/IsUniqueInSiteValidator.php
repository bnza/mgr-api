<?php

namespace App\Validator;

use App\Entity\Data\Individual;
use App\Entity\Data\Pottery;
use App\Service\Validator\ResourceSiteRelatedUniqueValidator;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class IsUniqueInSiteValidator extends ConstraintValidator
{
    public function __construct(
        private readonly ResourceSiteRelatedUniqueValidator $uniqueValidator,
    ) {
    }

    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof IsUniqueInSite) {
            throw new UnexpectedTypeException($constraint, IsUniqueInSite::class);
        }

        if (null === $value) {
            return;
        }

        if ($value instanceof Pottery) {
            $resourceClass = Pottery::class;
        } elseif ($value instanceof Individual) {
            $resourceClass = Individual::class;
        } else {
            return;
        }

        $identifierField = ResourceSiteRelatedUniqueValidator::SUPPORTED_RESOURCES[$resourceClass];

        $identifierGetter = 'get'.ucfirst($identifierField);
        $identifierValue = $value->$identifierGetter();

        $su = $value->getStratigraphicUnit();
        if (null === $su || null === $identifierValue) {
            return;
        }

        $criteria = [
            $identifierField => $identifierValue,
            'stratigraphicUnit' => $su->getId(),
        ];

        if (method_exists($value, 'getId')) {
            try {
                $id = $value->getId();
                if (null !== $id) {
                    $criteria['id'] = $id;
                }
            } catch (\Throwable $e) {
                // Ignore if not initialized or other errors
            }
        }

        if (!$this->uniqueValidator->isUnique($resourceClass, $criteria)) {
            $this->context->buildViolation($constraint->message)
                ->atPath($identifierField)
                ->addViolation();
        }
    }
}
