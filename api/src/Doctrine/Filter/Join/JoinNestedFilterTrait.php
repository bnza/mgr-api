<?php

namespace App\Doctrine\Filter\Join;

use ApiPlatform\Doctrine\Orm\Filter\FilterInterface;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\PropertyInfo\Type;

trait JoinNestedFilterTrait
{
    /**
     * Parse and validate a nested property, extracting configuration details for many-to-many filtering.
     *
     * Takes a dotted property string (e.g., 'stratigraphicUnit.description') and validates it against
     * the configured properties, extracting all necessary information for building many-to-many subqueries.
     *
     * @param string $property The nested property in format 'relationship.targetProperty' (e.g., 'stratigraphicUnit.description')
     *
     * @return array{
     *     relationshipProperty: string,
     *     targetProperty: string,
     *     joinEntity: string,
     *     targetEntity: string,
     *     sourceField: string,
     *     joinSourceField: string,
     *     joinTargetField: string,
     *     strategy: mixed
     * }|null Returns configuration array on success, null if property is not configured or invalid
     *
     * The returned array contains:
     * - relationshipProperty: The relationship property name (e.g., 'stratigraphicUnit')
     * - targetProperty: The target entity property name (e.g., 'description')
     * - joinEntity: The join table entity class name for many-to-many relationship
     * - targetEntity: The target entity class name to filter on
     * - sourceField: The field name on source entity (defaults to 'id')
     * - joinSourceField: The field name on join entity referencing source
     * - joinTargetField: The field name on join entity referencing target
     * - strategy: The filter strategy for the target property (null for simple properties)
     */
    protected function parseAndValidateProperty(string $property): ?array
    {
        [$relationshipProperty, $targetProperty] = explode('.', $property, 2);

        if (!isset($this->properties[$relationshipProperty])) {
            return null;
        }

        $config = $this->properties[$relationshipProperty];

        $joinEntity = $config['join_entity'];
        $targetEntity = $config['target_entity'];
        $sourceField = $config['source_field'] ?? 'id';
        $joinSourceField = $config['join_source_field'];
        $joinTargetField = $config['join_target_field'];
        $targetProperties = $config['target_properties'] ?? [];

        // Handle both associative array (key => strategy) and numeric array (value) formats
        $strategy = null;
        if (isset($targetProperties[$targetProperty])) {
            // Associative array format: ['description' => 'partial']
            $strategy = $targetProperties[$targetProperty];
        } elseif (in_array($targetProperty, $targetProperties, true)) {
            // Numeric array format: ['description']
            $strategy = null;
        } else {
            return null;
        }

        return [
            'relationshipProperty' => $relationshipProperty,
            'targetProperty' => $targetProperty,
            'joinEntity' => $joinEntity,
            'targetEntity' => $targetEntity,
            'sourceField' => $sourceField,
            'joinSourceField' => $joinSourceField,
            'joinTargetField' => $joinTargetField,
            'strategy' => $strategy,
        ];
    }

    /**
     * Apply target filter and create many-to-many subquery.
     */
    protected function applyTargetFilterWithSubquery(
        array $filterConfig,
        $value,
        FilterInterface $targetFilter,
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $resourceClass,
        ?Operation $operation = null,
        ?array $customContext = null,
    ): void {
        // Create subquery for target entity using the target filter
        $targetQueryBuilder = $this->managerRegistry
            ->getManagerForClass($filterConfig['targetEntity'])
            ->createQueryBuilder();

        $targetAlias = $queryNameGenerator->generateJoinAlias('target');
        $targetQueryBuilder
            ->select(sprintf('%s.id', $targetAlias))
            ->from($filterConfig['targetEntity'], $targetAlias);

        // Use custom context if provided, otherwise use default
        $context = $customContext ?? [
            'filters' => [$filterConfig['targetProperty'] => $value],
        ];

        // Apply the target filter logic to the target subquery
        $targetFilter->apply(
            $targetQueryBuilder,
            $queryNameGenerator,
            $filterConfig['targetEntity'],
            $operation,
            $context
        );

        // Create and execute the main subquery logic
        $this->applyManyToManySubquery(
            $queryBuilder,
            $queryNameGenerator,
            $resourceClass,
            $targetQueryBuilder,
            $filterConfig
        );
    }

    /**
     * Apply many-to-many subquery logic using the provided filter configuration.
     */
    protected function applyManyToManySubquery(
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $resourceClass,
        QueryBuilder $targetQueryBuilder,
        array $filterConfig,
    ): void {
        // Create main subquery that joins through the many-to-many relationship
        $mainSubQueryBuilder = $this->managerRegistry
            ->getManagerForClass($resourceClass)
            ->createQueryBuilder();

        $rootAlias = $queryBuilder->getRootAliases()[0];
        $mainSubAlias = $queryNameGenerator->generateJoinAlias('main_sub');
        $joinAlias = $queryNameGenerator->generateJoinAlias('join');

        $mainSubQueryBuilder
            ->select(sprintf('%s.%s', $mainSubAlias, $filterConfig['sourceField']))
            ->from($resourceClass, $mainSubAlias)
            ->innerJoin($filterConfig['joinEntity'], $joinAlias, 'WITH',
                sprintf('%s.%s = %s.%s', $mainSubAlias, $filterConfig['sourceField'], $joinAlias, $filterConfig['joinSourceField'])
            )
            ->where(sprintf('%s.%s IN (%s)', $joinAlias, $filterConfig['joinTargetField'], $targetQueryBuilder->getDQL()));

        // Merge parameters from target query
        foreach ($targetQueryBuilder->getParameters() as $parameter) {
            $mainSubQueryBuilder->setParameter($parameter->getName(), $parameter->getValue());
        }

        // Add the main subquery to the original query
        $queryBuilder->andWhere(
            sprintf('%s.%s IN (%s)', $rootAlias, $filterConfig['sourceField'], $mainSubQueryBuilder->getDQL())
        );

        // Merge parameters to main query
        foreach ($mainSubQueryBuilder->getParameters() as $parameter) {
            $queryBuilder->setParameter($parameter->getName(), $parameter->getValue());
        }
    }

    /**
     * Create base description entry with common properties.
     */
    protected function createBaseDescriptionEntry(string $filterProperty, string $type = Type::BUILTIN_TYPE_STRING): array
    {
        return [
            'property' => $filterProperty,
            'type' => $type,
            'required' => false,
        ];
    }

    /**
     * Generate standard getDescription implementation.
     */
    protected function generateStandardDescription(string $resourceClass): array
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

                $description = array_merge(
                    $description,
                    $this->generateDescriptionEntries($property, $targetProperty)
                );
            }
        }

        return $description;
    }

    /**
     * Create operator-based descriptions with OpenAPI examples.
     */
    protected function createOperatorDescriptions(
        string $filterProperty,
        string $property,
        string $targetProperty,
        array $operators,
        array $openApiExample = [],
    ): array {
        $description = [];

        foreach ($operators as $operator => $operatorDescription) {
            $entry = $this->createBaseDescriptionEntry(sprintf('%s[%s]', $filterProperty, $operator));
            $entry['description'] = sprintf(
                'Filter by %s.%s using many-to-many relationship (%s)',
                $property,
                $targetProperty,
                $operatorDescription
            );

            if (!empty($openApiExample)) {
                $entry['openapi'] = $openApiExample;
            }

            $description[sprintf('%s[%s]', $filterProperty, $operator)] = $entry;
        }

        return $description;
    }
}
