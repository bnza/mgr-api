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

        $parameters = new ArrayCollection();

        $codeParameter = new Parameter(
            $queryNameGenerator->generateParameterName('code'),
            $value.'%'
        );

        $parameters->add($codeParameter);

        $codeLikeExpression = $queryBuilder->expr()->like(
            'LOWER(o.code)',
            $queryBuilder->expr()->lower(':'.$codeParameter->getName())
        );

        $andWhere = $codeLikeExpression;

        if (mb_strlen($value) > 2) {
            $andWhere = $queryBuilder->expr()->orX();

            $nameParameter = new Parameter(
                $queryNameGenerator->generateParameterName('name'),
                '%'.$value.'%'
            );

            $parameters->add($nameParameter);

            $nameLikeExpression = $queryBuilder->expr()->like(
                'LOWER(unaccented(o.name))',
                'LOWER(unaccented(:'.$nameParameter->getName().'))'
            );
            $andWhere
                ->add($codeLikeExpression)
                ->add($nameLikeExpression);
        }

        $queryBuilder
            ->andWhere($andWhere)
            ->setParameters($parameters);
    }

    public function getDescription(string $resourceClass): array
    {
        return [
            'search' => [
                'property' => 'search',
                'type' => Type::BUILTIN_TYPE_STRING,
                'required' => false,
                'description' => 'Search case insensitive match across code (starts with) and name (contains). Up to two characters only code is matched.',
                'openapi' => [
                    'example' => 'me',
                    'allowReserved' => false,
                    'explode' => false,
                ],
            ],
        ];
    }
}
