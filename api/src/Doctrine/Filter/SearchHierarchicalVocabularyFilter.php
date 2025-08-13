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

final class SearchHierarchicalVocabularyFilter extends AbstractFilter
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
        $parameterName = $queryNameGenerator->generateParameterName('value');

        $queryBuilder->andWhere(
            $queryBuilder->expr()->orX(
                $queryBuilder->expr()->like("$rootAlias.group", ':'.$parameterName),
                $queryBuilder->expr()->like("$rootAlias.value", ':'.$parameterName)
            )
        )->setParameter($parameterName, '%'.$value.'%');
    }

    public function getDescription(string $resourceClass): array
    {
        return [
            'search' => [
                'property' => 'search',
                'type' => Type::BUILTIN_TYPE_STRING,
                'required' => false,
                'description' => 'Search case insensitive match across group and value fields',
                'openapi' => [
                    'allowReserved' => false,
                    'explode' => false,
                ],
            ],
        ];
    }
}
