<?php

declare(strict_types=1);

namespace App\Doctrine\Filter;

use ApiPlatform\Doctrine\Orm\Filter\AbstractFilter;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\TypeInfo\TypeIdentifier;

/**
 * Filter for the Bone class based on teeth property.
 *
 * When teeth parameter is truthy, filters to only include items where
 * code is IN('MAX','N'), otherwise returns the whole set.
 *
 * Usage:
 * - ?teeth=true - filters to only items with code 'MAX' or 'N'
 * - ?teeth=false - returns all items (no filtering)
 * - ?teeth=1 - filters to only items with code 'MAX' or 'N'
 * - ?teeth=0 - returns all items (no filtering)
 */
final class BoneTeethFilter extends AbstractFilter
{
    protected function filterProperty(
        string $property,
        $value,
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $resourceClass,
        ?Operation $operation = null,
        array $context = [],
    ): void {
        if (
            !$this->isPropertyEnabled($property, $resourceClass)
            || 'teeth' !== $property
        ) {
            return;
        }

        // Only apply filter if value is truthy
        if ($this->isTruthy($value)) {
            $alias = $queryBuilder->getRootAliases()[0];
            $parameterName = $queryNameGenerator->generateParameterName('teeth_codes');

            $queryBuilder
                ->andWhere(sprintf('%s.code IN (:%s)', $alias, $parameterName))
                ->setParameter($parameterName, ['MAX', 'N']);
        }

        // If value is falsy, we don't add any filtering (return whole set)
    }

    private function isTruthy($value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_string($value)) {
            $lowerValue = strtolower(trim($value));

            return !in_array($lowerValue, ['false', '0', '', 'no', 'off'], true);
        }

        if (is_numeric($value)) {
            return 0 !== (int) $value;
        }

        return (bool) $value;
    }

    public function getDescription(string $resourceClass): array
    {
        if (!$this->properties) {
            return [];
        }

        $description = [];

        foreach ($this->properties as $property => $config) {
            if ('teeth' === $property) {
                $description['teeth'] = [
                    'property' => 'teeth',
                    'type' => TypeIdentifier::BOOL,
                    'required' => false,
                    'description' => 'Filter by teeth - when true, shows only items with code MAX or N',
                    'openapi' => [
                        'example' => true,
                        'allowReserved' => false,
                        'allowEmptyValue' => true,
                        'explode' => false,
                    ],
                ];
            }
        }

        return $description;
    }
}
