<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraint;

/**
 * Validates that the Analysis type code assigned to a join entity's `$analysis` property
 * is one of the codes returned by the join entity's `getPermittedAnalysisTypes()`.
 */
#[\Attribute]
class PermittedAnalysisType extends Constraint
{
    public string $message = 'Analysis type "{{ code }}" is not permitted for {{ class }}. Allowed types: {{ allowed }}';

    public function getTargets(): string|array
    {
        return self::PROPERTY_CONSTRAINT;
    }
}
