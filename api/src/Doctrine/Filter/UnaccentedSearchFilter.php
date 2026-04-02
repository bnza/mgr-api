<?php

namespace App\Doctrine\Filter;

use ApiPlatform\Doctrine\Orm\Filter\AbstractFilter;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\OpenApi\Model\Parameter as OpenApiParameter;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\TypeInfo\Type\BuiltinType;

final class UnaccentedSearchFilter extends AbstractFilter
{
    protected function filterProperty(string $property, $value, QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, ?Operation $operation = null, array $context = []): void
    {
        $targets = $this->resolveTargets($property, $resourceClass);

        if ([] === $targets) {
            return;
        }

        $rootAlias = $queryBuilder->getRootAliases()[0];
        $orConditions = [];

        foreach ($targets as $targetProperty) {
            if (!$this->isPropertyMapped($targetProperty, $resourceClass)) {
                continue;
            }

            $alias = $rootAlias;
            $field = $targetProperty;

            if ($this->isPropertyNested($targetProperty, $resourceClass)) {
                [$alias, $field] = $this->addJoinsForNestedProperty(
                    $targetProperty,
                    $alias,
                    $queryBuilder,
                    $queryNameGenerator,
                    $resourceClass,
                    Join::LEFT_JOIN
                );
            }

            $parameterName = $queryNameGenerator->generateParameterName($field);

            $orConditions[] = sprintf(
                'LOWER(unaccented(%s.%s)) LIKE LOWER(unaccented(:%s))',
                $alias,
                $field,
                $parameterName
            );
            $queryBuilder->setParameter($parameterName, "%$value%");
        }

        if ([] === $orConditions) {
            return;
        }

        if (1 === count($orConditions)) {
            $queryBuilder->andWhere($orConditions[0]);
        } else {
            $queryBuilder->andWhere(
                $queryBuilder->expr()->orX(...$orConditions)
            );
        }
    }

    /**
     * Resolves the query parameter to one or more entity properties.
     *
     * Handles three cases:
     *   1. Alias with array:  'search' => ['title', 'author.value']  → ['title', 'author.value']
     *   2. Alias with string: 'search' => 'value'                    → ['value']
     *   3. Direct property:   'title' (normalized to 'title' => null) → ['title']
     *
     * @return string[]
     */
    private function resolveTargets(string $property, string $resourceClass): array
    {
        if (!$this->properties) {
            return [];
        }

        if (array_key_exists($property, $this->properties)) {
            $config = $this->properties[$property];

            if (is_array($config)) {
                return $config;
            }

            if (is_string($config)) {
                // Distinguish alias targets from filter strategies set by compiler passes
                // (e.g. StratigraphicUnitFiltersCompilerPass sets 'interpretation' => 'partial').
                // If the value is a mapped property, it's an alias (e.g. 'search' => 'value').
                // Otherwise it's a strategy hint and the key is the real property.
                if ($this->isPropertyMapped($config, $resourceClass)) {
                    return [$config];
                }

                return $this->isPropertyMapped($property, $resourceClass) ? [$property] : [];
            }

            if (null === $config) {
                return $this->isPropertyMapped($property, $resourceClass) ? [$property] : [];
            }
        }

        if (in_array($property, $this->properties, true)) {
            return [$property];
        }

        return [];
    }

    public function getDescription(string $resourceClass): array
    {
        if (!$this->properties) {
            return [];
        }

        $description = [];

        foreach ($this->properties as $key => $value) {
            if (is_int($key)) {
                $queryParam = $value;
                $targets = [$value];
            } else {
                $queryParam = $key;
                if (null === $value) {
                    $targets = [$key];
                } elseif (is_string($value)) {
                    $targets = [$value];
                } elseif (is_array($value)) {
                    $targets = $value;
                } else {
                    continue;
                }
            }

            $targetDescription = count($targets) > 1
                ? 'Searches across: '.implode(', ', $targets)
                : 'Filters on: '.$targets[0];

            $description[$queryParam] = [
                'property' => $queryParam,
                'type' => BuiltinType::string(),
                'required' => false,
                'description' => "Case insensitive unaccented string matching. $targetDescription",
                'openapi' => new OpenApiParameter(
                    name: $queryParam,
                    in: 'query',
                    allowEmptyValue: true,
                    explode: false,
                    allowReserved: false,
                    example: 'cafè',
                ),
            ];
        }

        return $description;
    }
}
