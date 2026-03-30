<?php

namespace App\Doctrine\Filter;

use ApiPlatform\Doctrine\Common\PropertyHelperTrait;
use ApiPlatform\Doctrine\Orm\Filter\AbstractFilter;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\TypeInfo\Type\BuiltinType;

/**
 * Filter for handling year ranges where a record may have a single year (lower)
 * or a range (lower and upper). It handles standard numeric filter operators:
 * [gt], [gte], [lt], [lte], [between], and exact match.
 */
final class OptionalUpperLimitRangeOverlapFilter extends AbstractFilter
{
    use PropertyHelperTrait;

    protected function filterProperty(
        string $property,
        mixed $value,
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $resourceClass,
        ?Operation $operation = null,
        array $context = [],
    ): void {
        // Only apply if the property is enabled in the configuration
        if (!$this->isPropertyEnabled($property, $resourceClass)) {
            return;
        }

        $config = $this->properties[$property] ?? [];
        $lowerProperty = $config['lowerProperty'] ?? $property;
        $upperProperty = $config['upperProperty'] ?? null;

        if (null === $upperProperty) {
            // If no upper property is configured, this filter behaves like a normal numeric filter
            // but we might want to default it to something or just return.
            // For this specific use case, we expect both to be configured.
            return;
        }

        $alias = $queryBuilder->getRootAliases()[0];
        $fieldLower = sprintf('%s.%s', $alias, $lowerProperty);
        $fieldUpper = sprintf('COALESCE(%s.%s, %s.%s)', $alias, $upperProperty, $alias, $lowerProperty);

        // If it's a direct value, treat as exact match (overlap)
        if (!is_array($value)) {
            $value = ['exact' => $value];
        }

        foreach ($value as $operator => $operand) {
            $this->applyOperator(
                $operator,
                $operand,
                $fieldLower,
                $fieldUpper,
                $queryBuilder,
                $queryNameGenerator,
                $property
            );
        }
    }

    private function applyOperator(
        string $operator,
        mixed $operand,
        string $fieldLower,
        string $fieldUpper,
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $property,
    ): void {
        $parameterName = $queryNameGenerator->generateParameterName($property.'_'.$operator);

        switch ($operator) {
            case 'exact':
                // Record overlaps if: lower <= target AND upper >= target
                $queryBuilder
                    ->andWhere(sprintf('%s <= :%s', $fieldLower, $parameterName))
                    ->andWhere(sprintf('%s >= :%s', $fieldUpper, $parameterName))
                    ->setParameter($parameterName, $operand);
                break;

            case 'gt':
                // Record overlaps if: upper > target
                $queryBuilder
                    ->andWhere(sprintf('%s > :%s', $fieldUpper, $parameterName))
                    ->setParameter($parameterName, $operand);
                break;

            case 'gte':
                // Record overlaps if: upper >= target
                $queryBuilder
                    ->andWhere(sprintf('%s >= :%s', $fieldUpper, $parameterName))
                    ->setParameter($parameterName, $operand);
                break;

            case 'lt':
                // Record overlaps if: lower < target
                $queryBuilder
                    ->andWhere(sprintf('%s < :%s', $fieldLower, $parameterName))
                    ->setParameter($parameterName, $operand);
                break;

            case 'lte':
                // Record overlaps if: lower <= target
                $queryBuilder
                    ->andWhere(sprintf('%s <= :%s', $fieldLower, $parameterName))
                    ->setParameter($parameterName, $operand);
                break;

            case 'between':
                // Standard between format is "start..end"
                if (!str_contains($operand, '..')) {
                    return;
                }

                [$start, $end] = explode('..', $operand);
                $paramStart = $queryNameGenerator->generateParameterName($property.'_between_start');
                $paramEnd = $queryNameGenerator->generateParameterName($property.'_between_end');

                // Overlap logic: (lower <= target_end) AND (upper >= target_start)
                $queryBuilder
                    ->andWhere(sprintf('%s <= :%s', $fieldLower, $paramEnd))
                    ->andWhere(sprintf('%s >= :%s', $fieldUpper, $paramStart))
                    ->setParameter($paramStart, $start)
                    ->setParameter($paramEnd, $end);
                break;
        }
    }

    public function getDescription(string $resourceClass): array
    {
        $description = [];

        foreach ($this->properties as $property => $strategy) {
            $description["$property"] = [
                'property' => $property,
                'type' => BuiltinType::int(),
                'required' => false,
                'strategy' => 'exact',
                'is_collection' => false,
            ];

            foreach (['gt', 'gte', 'lt', 'lte', 'between'] as $operator) {
                $description["$property"."[$operator]"] = [
                    'property' => $property,
                    'type' => 'between' === $operator ? BuiltinType::string() : BuiltinType::int(),
                    'required' => false,
                    'strategy' => $operator,
                    'is_collection' => false,
                ];
            }
        }

        return $description;
    }
}
