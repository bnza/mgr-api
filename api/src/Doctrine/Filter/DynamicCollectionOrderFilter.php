<?php

namespace App\Doctrine\Filter;

use ApiPlatform\Doctrine\Orm\Filter\AbstractFilter;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use Doctrine\ORM\QueryBuilder;

/**
 * A filter to dynamically order a collection (to-many relationship) using subqueries and aggregate functions.
 *
 * This filter addresses the issue where ordering by a field in a collection (e.g., a related entity's property)
 * would otherwise result in duplicate records in the result set due to SQL joins. It uses a subquery with
 * MIN (for ASC) or MAX (for DESC) aggregate functions to determine the order without affecting the result count.
 *
 * Use Case Example:
 * A WrittenSource entity has a one-to-many relationship with centuries (via WrittenSourceCentury).
 * To order WrittenSources by the chronology of their centuries:
 *
 * When ordering ASC, we want the WrittenSource whose earliest century starts first (MIN of chronologyLower).
 * When ordering DESC, we want the WrittenSource whose latest century ends last (MAX of chronologyUpper).
 *
 * Configuration in WrittenSource resource:
 * #[ApiFilter(
 *     DynamicCollectionOrderFilter::class,
 *     properties: [
 *         'centuries.century.chronologyLower' => [
 *             'centuries.century.chronologyLower', // Used for ASC: MIN(centuries.century.chronologyLower)
 *             'centuries.century.chronologyUpper', // Used for DESC: MAX(centuries.century.chronologyUpper)
 *         ],
 *     ]
 * )]
 *
 * API request:
 * /api/data/history/written_sources?order[centuries.century.chronologyLower]=asc
 */
final class DynamicCollectionOrderFilter extends AbstractFilter
{
    /**
     * {@inheritdoc}
     *
     * Applies the custom ordering logic if the property being filtered is 'order'.
     */
    protected function filterProperty(string $property, $value, QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, ?Operation $operation = null, array $context = []): void
    {
        if ('order' !== $property || !is_array($value)) {
            return;
        }

        foreach ($value as $propertyPath => $direction) {
            $config = $this->properties[$propertyPath] ?? null;
            if (!$config) {
                continue;
            }

            $isAsc = 'ASC' === strtoupper($direction);
            $targetFieldPath = $isAsc ? $config[0] : ($config[1] ?? $config[0]);
            $aggregateFunc = $isAsc ? 'MIN' : 'MAX';

            $this->applyOrder($targetFieldPath, $direction, $aggregateFunc, $queryBuilder, $queryNameGenerator);
        }
    }

    /**
     * Applies the ordering logic using a subquery to avoid duplicates when ordering by collection properties.
     *
     * @param string                      $propertyPath       The DQL property path to order by
     * @param string                      $direction          The sort direction ('ASC' or 'DESC')
     * @param string                      $aggregateFunc      The aggregate function to use in the subquery ('MIN' or 'MAX')
     * @param QueryBuilder                $queryBuilder       The main query builder
     * @param QueryNameGeneratorInterface $queryNameGenerator The query name generator
     */
    private function applyOrder(string $propertyPath, string $direction, string $aggregateFunc, QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator): void
    {
        $rootAlias = $queryBuilder->getRootAliases()[0];
        $rootEntity = $queryBuilder->getRootEntities()[0];
        $em = $queryBuilder->getEntityManager();
        $parts = explode('.', $propertyPath);
        $field = array_pop($parts);

        $subQb = $em->createQueryBuilder();
        $subAlias = 'sub_0';
        $subQb->from($rootEntity, $subAlias);

        $currentAlias = $subAlias;
        $currentEntity = $rootEntity;

        foreach ($parts as $i => $part) {
            $metadata = $em->getClassMetadata($currentEntity);
            $assocMapping = $metadata->getAssociationMapping($part);
            $targetEntity = $assocMapping->targetEntity;
            $nextAlias = 'sub_'.($i + 1);
            $subQb->join("$currentAlias.$part", $nextAlias);
            $currentEntity = $targetEntity;
            $currentAlias = $nextAlias;
        }

        $subQb->select("$aggregateFunc($currentAlias.$field)")
            ->where("$subAlias = $rootAlias");

        $orderAlias = $queryNameGenerator->generateParameterName('sort_'.$field);

        $queryBuilder
            ->addSelect('('.$subQb->getDQL().') AS HIDDEN '.$orderAlias)
            ->addOrderBy($orderAlias, $direction);
    }

    /**
     * {@inheritdoc}
     *
     * Provides metadata for the filter to be shown in the API documentation.
     */
    public function getDescription(string $resourceClass): array
    {
        $description = [];
        foreach ($this->properties as $property => $config) {
            $description["order[$property]"] = [
                'property' => $property,
                'type' => 'string',
                'required' => false,
                'schema' => [
                    'type' => 'string',
                    'enum' => ['asc', 'desc'],
                ],
            ];
        }

        return $description;
    }
}
