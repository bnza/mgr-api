<?php

namespace App\Doctrine\Filter;

use ApiPlatform\Doctrine\Orm\Filter\AbstractFilter;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;
use Symfony\Component\PropertyInfo\Type;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;

final class SearchSiteFilter extends AbstractFilter
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

        $codeParameter = $queryNameGenerator->generateParameterName('code');
        $nameParameter = $queryNameGenerator->generateParameterName('name');

        // Build OR conditions for multiple properties
        $orX = $queryBuilder->expr()->orX();

        // Search in code (starts with match, case-insensitive)
        $orX->add(
            $queryBuilder->expr()->like(
                'LOWER(o.code)',
                $queryBuilder->expr()->lower(':'.$codeParameter)
            )
        );

        // Search in name (contains match, unaccented)
        $orX->add(
            $queryBuilder->expr()->like(
                'LOWER(unaccented(o.name))',
                'LOWER(unaccented(:'.$nameParameter.'))'
            )
        );

        $queryBuilder
            ->andWhere($orX)
            ->setParameter($codeParameter, $value.'%')
            ->setParameter($nameParameter, '%'.$value.'%');
    }

    public function getDescription(string $resourceClass): array
    {
        return [
            'search' => [
                'property' => 'search',
                'type' => Type::BUILTIN_TYPE_STRING,
                'required' => false,
                'description' => 'Search case insensitive match across code (starts with) and name (contains)',
                'openapi' => [
                    'example' => 'me',
                    'allowReserved' => false,
                    'allowEmptyValue' => true,
                    'explode' => false,
                ],
            ],
        ];
    }
}
