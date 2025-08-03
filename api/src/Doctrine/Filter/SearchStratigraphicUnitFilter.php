<?php

namespace App\Doctrine\Filter;

use ApiPlatform\Doctrine\Orm\Filter\AbstractFilter;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Query\Parameter;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;
use Symfony\Component\PropertyInfo\Type;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;

final class SearchStratigraphicUnitFilter extends AbstractFilter
{
    public function __construct(
        ?ManagerRegistry $managerRegistry = null,
        ?LoggerInterface $logger = null,
        ?array $properties = ['search'],
        ?NameConverterInterface $nameConverter = null,
    ) {
        parent::__construct($managerRegistry, $logger, $properties, $nameConverter);
    }

    protected function filterProperty(string $property, $value, QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, ?Operation $operation = null, array $context = []): void
    {
        // Only handle the 'search' property
        if ('search' !== $property) {
            return;
        }

        if (empty($value)) {
            return;
        }

        $rootAlias = $queryBuilder->getRootAliases()[0];
        $parameters = new ArrayCollection();

        // Split the value using non-word characters
        $chunks = $this->splitValue($value);
        $chunkCount = count($chunks);

        // Join with site table for site.code access
        $siteAlias = $queryNameGenerator->generateJoinAlias('site');
        $queryBuilder->leftJoin($rootAlias.'.site', $siteAlias);

        $whereConditions = [];

        if (1 === $chunkCount) {
            $chunk = $chunks[0];
            if (is_numeric($chunk)) {
                // a.2: is numeric -> filter by stratigraphic number
                $whereConditions[] = $this->createNumberCondition($queryBuilder, $queryNameGenerator, $rootAlias, $chunk, $parameters);
            } else {
                // a.1: is string -> filter by site code
                $whereConditions[] = $this->createSiteCodeCondition($queryBuilder, $queryNameGenerator, $siteAlias, $chunk, $parameters);
            }
        } elseif (2 === $chunkCount) {
            $chunk1 = $chunks[0];
            $chunk2 = $chunks[1];

            if (!is_numeric($chunk1) && is_numeric($chunk2)) {
                // b.1: c1 is string, c2 is numeric -> site code AND number
                $whereConditions[] = $this->createSiteCodeCondition($queryBuilder, $queryNameGenerator, $siteAlias, $chunk1, $parameters);
                $whereConditions[] = $this->createNumberCondition($queryBuilder, $queryNameGenerator, $rootAlias, $chunk2, $parameters);
            } elseif (is_numeric($chunk1) && is_numeric($chunk2)) {
                // b.2: c1 is numeric, c2 is numeric -> year AND number
                $whereConditions[] = $this->createYearCondition($queryBuilder, $queryNameGenerator, $rootAlias, $chunk1, $parameters);
                $whereConditions[] = $this->createNumberCondition($queryBuilder, $queryNameGenerator, $rootAlias, $chunk2, $parameters);
            } else {
                // Invalid combination -> return empty set
                $whereConditions[] = $this->createEmptySetCondition($queryBuilder, $siteAlias);
            }
        } elseif ($chunkCount >= 3) {
            // c.1 and d.1: use first 3 chunks -> site code, year, number
            $chunk1 = $chunks[0];
            $chunk2 = $chunks[1];
            $chunk3 = $chunks[2];

            if (!is_numeric($chunk1) && is_numeric($chunk2) && is_numeric($chunk3)) {
                // c1 is string, c2 is numeric, c3 is numeric -> site code AND year AND number
                $whereConditions[] = $this->createSiteCodeCondition($queryBuilder, $queryNameGenerator, $siteAlias, $chunk1, $parameters);
                $whereConditions[] = $this->createYearCondition($queryBuilder, $queryNameGenerator, $rootAlias, $chunk2, $parameters);
                $whereConditions[] = $this->createNumberCondition($queryBuilder, $queryNameGenerator, $rootAlias, $chunk3, $parameters);
            } else {
                // Invalid combination -> return empty set
                $whereConditions[] = $this->createEmptySetCondition($queryBuilder, $siteAlias);
            }
        }

        if (!empty($whereConditions)) {
            $queryBuilder
                ->andWhere($queryBuilder->expr()->andX(...$whereConditions))
                ->setParameters($parameters);
        }
    }

    private function splitValue(string $value): array
    {
        // Split using any non-alphanumeric characters group and filter out empty strings
        return array_filter(preg_split('/[^a-zA-Z0-9]+/', $value), fn ($chunk) => '' !== $chunk);
    }

    private function createSiteCodeCondition(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $siteAlias, string $value, ArrayCollection $parameters): string
    {
        $parameterName = $queryNameGenerator->generateParameterName('site_code');
        $parameter = new Parameter($parameterName, '%'.strtoupper($value));
        $parameters->add($parameter);

        return $queryBuilder->expr()->like(
            "UPPER($siteAlias.code)",
            ':'.$parameterName
        );
    }

    private function createNumberCondition(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $rootAlias, string $value, ArrayCollection $parameters): string
    {
        $parameterName = $queryNameGenerator->generateParameterName('su_number');
        $parameter = new Parameter($parameterName, '%'.$value);
        $parameters->add($parameter);

        return $queryBuilder->expr()->like(
            "CAST($rootAlias.number AS TEXT)",
            ':'.$parameterName
        );
    }

    private function createYearCondition(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $rootAlias, string $value, ArrayCollection $parameters): string
    {
        $parameterName = $queryNameGenerator->generateParameterName('su_year');
        $parameter = new Parameter($parameterName, '%'.$value);
        $parameters->add($parameter);

        return $queryBuilder->expr()->like(
            "CAST($rootAlias.year AS TEXT)",
            ':'.$parameterName
        );
    }

    private function createEmptySetCondition(QueryBuilder $queryBuilder, string $siteAlias): string
    {
        return $queryBuilder->expr()->isNull($siteAlias.'.code');
    }

    public function getDescription(string $resourceClass): array
    {
        return [
            'search' => [
                'property' => 'search',
                'type' => Type::BUILTIN_TYPE_STRING,
                'required' => false,
                'description' => 'Search stratigraphic units by splitting input on non-word characters. Supports: 1 chunk (site code or number), 2 chunks (site+number or year+number), 3+ chunks (site+year+number). Invalid combinations return empty results.',
                'openapi' => [
                    'example' => '2025 123',
                    'allowReserved' => false,
                    'explode' => false,
                ],
            ],
        ];
    }
}
