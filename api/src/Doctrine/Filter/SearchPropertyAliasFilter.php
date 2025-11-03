<?php

namespace App\Doctrine\Filter;

use ApiPlatform\Doctrine\Orm\Filter\AbstractFilter;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;
use Symfony\Component\TypeInfo\Type\BuiltinType;

class SearchPropertyAliasFilter extends AbstractFilter
{
    public function __construct(
        ?ManagerRegistry $managerRegistry = null,
        ?LoggerInterface $logger = null,
        ?array $properties = null, // expects mapping like ['search' => 'value']
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
        // Only act on parameters that are defined as aliases in properties mapping
        $mapping = $this->getProperties() ?? [];
        if (!\array_key_exists($property, $mapping)) {
            return;
        }

        $search = trim((string) $value);
        if ('' === $search) {
            return;
        }

        $targetProperty = $mapping[$property]; // e.g. 'value'

        $rootAlias = $queryBuilder->getRootAliases()[0];
        $parameterName = $queryNameGenerator->generateParameterName($targetProperty);
        $queryBuilder
            ->andWhere(
                sprintf('LOWER(unaccented(%s.%s)) LIKE LOWER(unaccented(:%s))', $rootAlias, $targetProperty, $parameterName)
            )
            ->setParameter($parameterName, '%'.strtolower($search).'%');
    }

    public function getDescription(string $resourceClass): array
    {
        $mapping = $this->getProperties() ?? [];
        $desc = [];

        foreach ($mapping as $alias => $target) {
            $desc[$alias] = [
                'property' => $alias,
                'type' => BuiltinType::string(),
                'required' => false,
                'description' => sprintf("Case-insensitive contains search; alias '%s' targets '%s. Nested properties are not supported", $alias, $target),
                'openapi' => [
                    'example' => 'oak',
                ],
            ];
        }

        return $desc;
    }
}
