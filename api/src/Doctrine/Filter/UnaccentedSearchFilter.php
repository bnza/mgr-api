<?php

namespace App\Doctrine\Filter;

use ApiPlatform\Doctrine\Orm\Filter\AbstractFilter;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\OpenApi\Model\Parameter as OpenApiParameter;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\TypeInfo\Type\BuiltinType;

final class UnaccentedSearchFilter extends AbstractFilter
{
    protected function filterProperty(string $property, $value, QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, ?Operation $operation = null, array $context = []): void
    {
        if (
            !$this->isPropertyEnabled($property, $resourceClass)
            || !$this->isPropertyMapped($property, $resourceClass)
        ) {
            return;
        }

        $alias = $queryBuilder->getRootAliases()[0];
        $field = $property;

        if ($this->isPropertyNested($property, $resourceClass)) {
            // The correct signature for addJoinsForNestedProperty
            [$alias, $field] = $this->addJoinsForNestedProperty($property, $alias, $queryBuilder, $queryNameGenerator, $resourceClass, Join::LEFT_JOIN
            );
        }

        $parameterName = $queryNameGenerator->generateParameterName($property);

        $queryBuilder
            ->andWhere(sprintf('LOWER(unaccented(%s.%s)) LIKE LOWER(unaccented(:%s))', $alias, $field, $parameterName))
            ->setParameter($parameterName, "%$value%");
    }

    public function getDescription(string $resourceClass): array
    {
        if (!$this->properties) {
            return [];
        }
        $description = [];
        foreach ($this->properties as $property => $strategy) {
            $description["$property"] = [
                'property' => $property,
                'type' => BuiltinType::string(),
                'required' => false,
                'description' => 'Filter using case insensitive unaccented string matching',
                'openapi' => new OpenApiParameter(
                    name: $property,
                    in: 'query',
                    allowEmptyValue: true,
                    explode: false,
                    allowReserved: false,
                    example: 'cafè',
                ),
            ];
        }

        return $description;
    }
}
