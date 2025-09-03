<?php

declare(strict_types=1);

namespace App\Doctrine\Filter;

use ApiPlatform\Doctrine\Orm\Filter\AbstractFilter;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\PropertyInfo\Type;

/**
 * Filter for performing bitwise operations on integer fields.
 *
 * This filter allows checking if specific bits are set in a bitmap field
 * using the custom BIT_AND DQL function.
 *
 * Usage:
 * - ?property[and]=5 - checks if bits 1 and 4 are set (5 = 101 in binary)
 * - ?property[any]=3 - checks if any of bits 1 or 2 are set (3 = 011 in binary)
 * - ?property[exact]=8 - checks if the value equals exactly 8
 */
final class BitmapFilter extends AbstractFilter
{
    public const string STRATEGY_AND = 'and';
    public const string STRATEGY_ANY = 'any';

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
            || !$this->isPropertyMapped($property, $resourceClass)
        ) {
            return;
        }

        // Handle array of values with strategies
        if (is_array($value)) {
            foreach ($value as $strategy => $strategyValue) {
                $this->applyBitmapFilter(
                    $property,
                    $strategyValue,
                    $strategy,
                    $queryBuilder,
                    $queryNameGenerator,
                    $resourceClass
                );
            }
        } else {
            // Default to 'and' strategy if no strategy is specified
            $this->applyBitmapFilter(
                $property,
                $value,
                self::STRATEGY_AND,
                $queryBuilder,
                $queryNameGenerator,
                $resourceClass
            );
        }
    }

    private function applyBitmapFilter(
        string $property,
        $value,
        string $strategy,
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $resourceClass,
    ): void {
        if (null === $value || '' === $value) {
            return;
        }

        // Convert to integer and validate
        $bitmask = (int) $value;
        if ($bitmask <= 0) {
            return;
        }

        $alias = $queryBuilder->getRootAliases()[0];
        $field = $property;

        if ($this->isPropertyNested($property, $resourceClass)) {
            [$alias, $field] = $this->addJoinsForNestedProperty(
                $property,
                $alias,
                $queryBuilder,
                $queryNameGenerator,
                $resourceClass,
                Join::LEFT_JOIN
            );
        }

        $parameterName = $queryNameGenerator->generateParameterName($property.'_'.$strategy);

        switch ($strategy) {
            case self::STRATEGY_AND:
                // All specified bits must be set: BIT_AND(field, mask) = mask
                $queryBuilder
                    ->andWhere(
                        sprintf('BIT_AND(%s.%s, :%s) = :%s', $alias, $field, $parameterName, $parameterName)
                    )
                    ->setParameter($parameterName, $bitmask);
                break;

            case self::STRATEGY_ANY:
                // Any of the specified bits must be set: BIT_AND(field, mask) > 0
                $queryBuilder
                    ->andWhere(
                        sprintf('BIT_AND(%s.%s, :%s) > 0', $alias, $field, $parameterName)
                    )
                    ->setParameter($parameterName, $bitmask);
                break;
        }
    }

    public function getDescription(string $resourceClass): array
    {
        if (!$this->properties) {
            return [];
        }

        $description = [];
        foreach ($this->properties as $property => $strategy) {
            $description["{$property}[and]"] = [
                'property' => $property,
                'type' => Type::BUILTIN_TYPE_INT,
                'required' => false,
                'description' => 'Filter by bitwise AND operation - all specified bits must be set',
                'openapi' => [
                    'example' => 5,
                    'allowReserved' => false,
                    'allowEmptyValue' => true,
                    'explode' => false,
                ],
            ];

            $description["{$property}[any]"] = [
                'property' => $property,
                'type' => Type::BUILTIN_TYPE_INT,
                'required' => false,
                'description' => 'Filter by bitwise AND operation - any of the specified bits must be set',
                'openapi' => [
                    'example' => 3,
                    'allowReserved' => false,
                    'allowEmptyValue' => true,
                    'explode' => false,
                ],
            ];
        }

        return $description;
    }
}
