<?php

namespace App\Doctrine\Filter\Join;

use ApiPlatform\Doctrine\Orm\Filter\AbstractFilter;
use ApiPlatform\Doctrine\Orm\Filter\ExistsFilter;
use ApiPlatform\Doctrine\Orm\Filter\FilterInterface;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\PropertyInfo\Type;

class ExistsJoinNestedFilter extends AbstractFilter implements FilterInterface
{
    use JoinNestedFilterTrait;

    protected function filterProperty(
        string $property,
        $value,
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $resourceClass,
        ?Operation $operation = null,
        array $context = [],
    ): void {
        // Handle exists[property.name] format
        if ('exists' !== $property || !is_array($value)) {
            return;
        }

        foreach ($value as $nestedProperty => $existsValue) {
            $this->filterNestedProperty($nestedProperty, $existsValue, $queryBuilder, $queryNameGenerator, $resourceClass, $operation, $context);
        }
    }

    private function filterNestedProperty(
        string $property,
        $value,
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $resourceClass,
        ?Operation $operation = null,
        array $context = [],
    ): void {
        if (null === $value || '' === $value || !str_contains($property, '.')) {
            return;
        }

        $filterConfig = $this->parseAndValidateProperty($property);
        if (!$filterConfig) {
            return;
        }

        // Create the target ExistsFilter instance
        $targetFilter = new ExistsFilter(
            $this->managerRegistry,
            $this->logger,
            [$filterConfig['targetProperty'] => null],
            'exists',
            $this->nameConverter,
        );

        // Apply target filter with custom context for ExistsFilter
        $customContext = [
            'filters' => ['exists' => [$filterConfig['targetProperty'] => $value]],
        ];

        $this->applyTargetFilterWithSubquery(
            $filterConfig,
            $value,
            $targetFilter,
            $queryBuilder,
            $queryNameGenerator,
            $resourceClass,
            $operation,
            $customContext
        );
    }

    public function getDescription(string $resourceClass): array
    {
        if (!$this->properties) {
            return [];
        }

        $description = [];

        foreach ($this->properties as $property => $config) {
            if (!is_array($config) || !isset($config['target_properties'])) {
                continue;
            }

            foreach ($config['target_properties'] as $targetProperty => $strategy) {
                // Handle both associative and numeric array formats
                if (is_numeric($targetProperty)) {
                    // Numeric array format: ['description']
                    $targetProperty = $strategy;
                    $strategy = null;
                }

                $filterProperty = sprintf('%s.%s', $property, $targetProperty);

                $entry = $this->createBaseDescriptionEntry(sprintf('exists[%s]', $filterProperty), Type::BUILTIN_TYPE_BOOL);
                $entry['description'] = sprintf(
                    'Filter by %s.%s using many-to-many relationship (check if property exists)',
                    $property,
                    $targetProperty
                );
                $entry['openapi'] = [
                    'example' => true,
                    'allowReserved' => false,
                    'explode' => false,
                ];

                $description[sprintf('exists[%s]', $filterProperty)] = $entry;
            }
        }

        return $description;
    }
}
