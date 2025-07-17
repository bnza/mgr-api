<?php

namespace App\Doctrine\Filter;

use ApiPlatform\Doctrine\Orm\Filter\AbstractFilter;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\PropertyInfo\Type;

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

        $parameterName = $queryNameGenerator->generateParameterName($property);
        $queryBuilder
            ->andWhere(sprintf('LOWER(unaccented(o.%s)) LIKE LOWER(unaccented(:%s))', $property, $parameterName))
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
                'type' => Type::BUILTIN_TYPE_STRING,
                'required' => false,
                'description' => 'Filter using case insensitive unaccented string matching',
                'openapi' => [
                    'example' => 'cafè',
                    /*
                     * If true, query parameters will be not percent-encoded
                     */
                    'allowReserved' => false,
                    'allowEmptyValue' => true,
                    /*
                     * To be true, the type must be Type::BUILTIN_TYPE_ARRAY, ?product=blue,green will be ?product[]=blue&product[]=green
                     */
                    'explode' => false,
                ],
            ];
        }

        return $description;
    }
}
