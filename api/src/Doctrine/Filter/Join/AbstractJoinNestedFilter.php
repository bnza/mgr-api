<?php

namespace App\Doctrine\Filter\Join;

use ApiPlatform\Doctrine\Orm\Filter\AbstractFilter;
use ApiPlatform\Doctrine\Orm\Filter\FilterInterface;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use Doctrine\ORM\QueryBuilder;

abstract class AbstractJoinNestedFilter extends AbstractFilter implements FilterInterface
{
    protected function filterProperty(
        string                      $property,
                                    $value,
        QueryBuilder                $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string                      $resourceClass,
        ?Operation                  $operation = null,
        array                       $context = [],
    ): void
    {
        if (null === $value || '' === $value || !str_contains($property, '.')) {
            return;
        }

        $baseProperty = $this->extractBaseProperty($property);

        if (!str_contains($baseProperty, '.')) {
            return;
        }

        [$relationshipProperty, $targetProperty] = explode('.', $baseProperty, 2);

        if (!isset($this->properties[$relationshipProperty])) {
            return;
        }

        $config = $this->properties[$relationshipProperty];

        $joinEntity = $config['join_entity'];
        $targetEntity = $config['target_entity'];
        $sourceField = $config['source_field'] ?? 'id';
        $joinSourceField = $config['join_source_field'];
        $joinTargetField = $config['join_target_field'];
        $targetProperties = $config['target_properties'] ?? [];

        if (!isset($targetProperties[$targetProperty])) {
            return;
        }

        // Create the target filter instance
        $targetFilter = $this->createTargetFilter($targetProperty, $targetProperties[$targetProperty]);

        // Create subquery for target entity using the target filter
        $targetQueryBuilder = $this->managerRegistry
            ->getManagerForClass($targetEntity)
            ->createQueryBuilder();

        $targetAlias = $queryNameGenerator->generateJoinAlias('target');
        $targetQueryBuilder
            ->select(sprintf('%s.id', $targetAlias))
            ->from($targetEntity, $targetAlias);

        // Apply the target filter logic to the target subquery
        $targetFilter->apply(
            $targetQueryBuilder,
            $queryNameGenerator,
            $targetEntity,
            $operation,
            [
                'filters' => [$targetProperty => $value], // Fixed: use $targetProperty instead of $property
            ]
        );

        // Create and execute the main subquery logic
        $this->applyManyToManySubquery(
            $queryBuilder,
            $queryNameGenerator,
            $resourceClass,
            $targetQueryBuilder,
            $sourceField,
            $joinEntity,
            $joinSourceField,
            $joinTargetField
        );
    }

    protected function applyManyToManySubquery(
        QueryBuilder                $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string                      $resourceClass,
        QueryBuilder                $targetQueryBuilder,
        string                      $sourceField,
        string                      $joinEntity,
        string                      $joinSourceField,
        string                      $joinTargetField
    ): void
    {
        // Create main subquery that joins through the many-to-many relationship
        $mainSubQueryBuilder = $this->managerRegistry
            ->getManagerForClass($resourceClass)
            ->createQueryBuilder();

        $rootAlias = $queryBuilder->getRootAliases()[0];
        $mainSubAlias = $queryNameGenerator->generateJoinAlias('main_sub');
        $joinAlias = $queryNameGenerator->generateJoinAlias('join');

        $mainSubQueryBuilder
            ->select(sprintf('%s.%s', $mainSubAlias, $sourceField))
            ->from($resourceClass, $mainSubAlias)
            ->innerJoin($joinEntity, $joinAlias, 'WITH',
                sprintf('%s.%s = %s.%s', $mainSubAlias, $sourceField, $joinAlias, $joinSourceField)
            )
            ->where(sprintf('%s.%s IN (%s)', $joinAlias, $joinTargetField, $targetQueryBuilder->getDQL()));

        // Merge parameters from target query
        foreach ($targetQueryBuilder->getParameters() as $parameter) {
            $mainSubQueryBuilder->setParameter($parameter->getName(), $parameter->getValue());
        }

        // Add the main subquery to the original query
        $queryBuilder->andWhere(
            sprintf('%s.%s IN (%s)', $rootAlias, $sourceField, $mainSubQueryBuilder->getDQL())
        );

        // Merge parameters to main query
        foreach ($mainSubQueryBuilder->getParameters() as $parameter) {
            $queryBuilder->setParameter($parameter->getName(), $parameter->getValue());
        }
    }

    protected function extractBaseProperty(string $property): string
    {
        return $property;
    }

    protected function generateDescriptionEntries(string $property, string $targetProperty): array
    {
        $filterProperty = sprintf('%s.%s', $property, $targetProperty);

        return [
            $filterProperty => [
                'property' => $filterProperty,
                'type' => 'string',
                'required' => false,
                'description' => sprintf(
                    'Filter by %s.%s using many-to-many relationship',
                    $property,
                    $targetProperty
                ),
            ]
        ];
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
                $description = array_merge(
                    $description,
                    $this->generateDescriptionEntries($property, $targetProperty)
                );
            }
        }

        return $description;
    }

    abstract protected function createTargetFilter(string $targetProperty, $strategy): FilterInterface;
}
