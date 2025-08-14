<?php

namespace App\Doctrine\Filter;

use ApiPlatform\Doctrine\Orm\Filter\AbstractFilter;
use ApiPlatform\Doctrine\Orm\Filter\FilterInterface;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\IriConverterInterface;
use ApiPlatform\Metadata\Operation;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;

class GenericManyToManyNestedFilter extends AbstractFilter implements FilterInterface
{
    public function __construct(
        private readonly IriConverterInterface $iriConverter,
        ManagerRegistry $managerRegistry,
        ?LoggerInterface $logger = null,
        ?array $properties = null,
        ?NameConverterInterface $nameConverter = null,
    ) {
        parent::__construct($managerRegistry, $logger, $properties, $nameConverter);
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

                $description[$filterProperty] = [
                    'property' => $filterProperty,
                    'type' => 'string',
                    'required' => false,
                    'description' => sprintf(
                        'Filter by %s.%s using many-to-many relationship',
                        $property,
                        $targetProperty
                    ),
                ];
            }
        }

        return $description;
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

        if (!isset($targetProperties[$targetProperty])) {
            return;
        }

        // Create a temporary SearchFilter for the target entity
        $targetSearchFilter = new SearchFilter(
            $this->managerRegistry,
            $this->iriConverter,
            null,
            $this->logger,
            [$targetProperty => $targetProperties[$targetProperty]],
            null,
            $this->nameConverter,
        );

        // Create subquery for target entity using the existing SearchFilter
        $targetQueryBuilder = $this->managerRegistry
            ->getManagerForClass($targetEntity)
            ->createQueryBuilder();

        $targetAlias = $queryNameGenerator->generateJoinAlias('target');
        $targetQueryBuilder
            ->select(sprintf('%s.id', $targetAlias))
            ->from($targetEntity, $targetAlias);

        // Apply the SearchFilter logic to the target subquery
        $targetSearchFilter->apply(
            $targetQueryBuilder,
            $queryNameGenerator,
            $targetEntity,
            $operation,
            [
                'filters' => [$targetProperty => $value],
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
}
