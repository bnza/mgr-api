<?php

namespace App\Doctrine\Filter\Join;

use ApiPlatform\Doctrine\Orm\Filter\AbstractFilter;
use ApiPlatform\Doctrine\Orm\Filter\FilterInterface;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use Doctrine\ORM\QueryBuilder;

/**
 * @deprecated This filter is deprecated as API Platform handles many-to-many join queries natively.
 *             Use the standard API Platform filters with dot notation (e.g., 'contextStratigraphicUnits.stratigraphicUnit.year').
 *             The only drawback is that the IN subquery is hardcoded with ID values that satisfy the criteria.
 */
abstract class AbstractJoinNestedFilter extends AbstractFilter implements FilterInterface
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
        if (null === $value || '' === $value || !str_contains($property, '.')) {
            return;
        }

        $baseProperty = $this->extractBaseProperty($property);

        if (!str_contains($baseProperty, '.')) {
            return;
        }

        $filterConfig = $this->parseAndValidateProperty($baseProperty);
        if (!$filterConfig) {
            return;
        }

        // Create the target filter instance
        $targetFilter = $this->createTargetFilter($filterConfig['targetProperty'], $filterConfig['strategy']);

        // Apply target filter with subquery using default context
        $this->applyTargetFilterWithSubquery(
            $filterConfig,
            $value,
            $targetFilter,
            $queryBuilder,
            $queryNameGenerator,
            $resourceClass,
            $operation
        );
    }

    protected function extractBaseProperty(string $property): string
    {
        return $property;
    }

    protected function generateDescriptionEntries(string $property, string $targetProperty): array
    {
        $filterProperty = sprintf('%s.%s', $property, $targetProperty);
        $entry = $this->createBaseDescriptionEntry($filterProperty);
        $entry['description'] = sprintf(
            'Filter by %s.%s using many-to-many relationship',
            $property,
            $targetProperty
        );

        return [$filterProperty => $entry];
    }

    public function getDescription(string $resourceClass): array
    {
        return $this->generateStandardDescription($resourceClass);
    }

    abstract protected function createTargetFilter(string $targetProperty, $strategy): FilterInterface;
}
