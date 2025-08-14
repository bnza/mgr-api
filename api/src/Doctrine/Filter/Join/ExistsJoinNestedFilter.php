<?php

namespace App\Doctrine\Filter\Join;

use ApiPlatform\Doctrine\Orm\Filter\AbstractFilter;
use ApiPlatform\Doctrine\Orm\Filter\ExistsFilter;
use ApiPlatform\Doctrine\Orm\Filter\FilterInterface;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;
use Symfony\Component\PropertyInfo\Type;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;

class ExistsJoinNestedFilter extends AbstractFilter implements FilterInterface
{
    public function __construct(
        ManagerRegistry $managerRegistry,
        ?LoggerInterface $logger = null,
        ?array $properties = null,
        ?NameConverterInterface $nameConverter = null,
    ) {
        parent::__construct($managerRegistry, $logger, $properties, $nameConverter);
    }

    protected function filterProperty(
        string $property,
        $value,
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $resourceClass,
        ?Operation $operation = null,
        array $context = [],
    ): void {
        // Handle exists[property] format where property can be nested
        if ('exists' !== $property || !is_array($value)) {
            return;
        }

        // Handle nested structure from API Platform parsing
        foreach ($value as $relationshipProperty => $nestedValue) {
            if (is_array($nestedValue)) {
                // Handle exists[stratigraphicUnit][description] format
                foreach ($nestedValue as $targetProperty => $existsValue) {
                    $fullProperty = $relationshipProperty.'.'.$targetProperty;
                    $this->filterNestedProperty($fullProperty, $existsValue, $queryBuilder, $queryNameGenerator, $resourceClass, $operation, $context);
                }
            } else {
                // Handle direct property if it contains a dot
                if (str_contains($relationshipProperty, '.')) {
                    $this->filterNestedProperty($relationshipProperty, $nestedValue, $queryBuilder, $queryNameGenerator, $resourceClass, $operation, $context);
                }
            }
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

        [$relationshipProperty, $targetProperty] = explode('.', $property, 2);

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

        // Fix: Check if targetProperty is in the array (not as a key)
        if (!in_array($targetProperty, $targetProperties, true)) {
            return;
        }

        // Convert string 'false' to boolean false
        $boolValue = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        if (null === $boolValue) {
            $boolValue = !empty($value);
        }

        // Create the target ExistsFilter instance
        $targetFilter = new ExistsFilter(
            $this->managerRegistry,
            $this->logger,
            [$targetProperty => null],
            'exists',
            $this->nameConverter,
        );

        // Create subquery for target entity using the ExistsFilter
        $targetQueryBuilder = $this->managerRegistry
            ->getManagerForClass($targetEntity)
            ->createQueryBuilder();

        $targetAlias = $queryNameGenerator->generateJoinAlias('target');
        $targetQueryBuilder
            ->select(sprintf('%s.id', $targetAlias))
            ->from($targetEntity, $targetAlias);

        // Apply the ExistsFilter logic to the target subquery
        $targetFilter->apply(
            $targetQueryBuilder,
            $queryNameGenerator,
            $targetEntity,
            $operation,
            [
                'filters' => ['exists' => [$targetProperty => $value]],
            ]
        );

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
                sprintf('%s = %s.%s', $mainSubAlias, $joinAlias, $joinSourceField)
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
                $filterProperty = sprintf('%s.%s', $property, $targetProperty);

                $description[sprintf('exists[%s]', $filterProperty)] = [
                    'property' => $filterProperty,
                    'type' => Type::BUILTIN_TYPE_BOOL,
                    'required' => false,
                    'description' => sprintf(
                        'Filter by %s.%s using many-to-many relationship (check if property exists)',
                        $property,
                        $targetProperty
                    ),
                    'openapi' => [
                        'example' => true,
                        'allowReserved' => false,
                        'explode' => false,
                    ],
                ];
            }
        }

        return $description;
    }
}
