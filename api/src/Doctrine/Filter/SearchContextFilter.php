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
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;
use Symfony\Component\TypeInfo\Type\BuiltinType;

final class SearchContextFilter extends AbstractFilter
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

        $value = trim($value);

        if (empty($value)) {
            return;
        }

        $rootAlias = $queryBuilder->getRootAliases()[0];
        $parameters = $queryBuilder->getParameters();

        // Split value at the first dot and trim chunks
        $chunks = array_map('trim', explode('.', $value, 2));

        if (1 === count($chunks)) {
            // Single chunk: case insensitive like name
            $nameExpression = $this->createNameLikeExpression($queryBuilder, $queryNameGenerator, $rootAlias, $chunks[0], $parameters);
            $queryBuilder->andWhere($nameExpression);
        } else {
            // Two chunks: handle edge cases
            $siteCodeChunk = trim($chunks[0]);
            $nameChunk = trim($chunks[1]);

            // Edge case: empty site code chunk (e.g., ".fill") -> only search by name
            if (empty($siteCodeChunk)) {
                $nameExpression = $this->createNameLikeExpression($queryBuilder, $queryNameGenerator, $rootAlias, $nameChunk, $parameters);
                $queryBuilder->andWhere($nameExpression);
            } // Edge case: empty name chunk (e.g., "MO ." or "MO .  ") -> only search by site code
            elseif (empty($nameChunk)) {
                $siteAlias = $queryNameGenerator->generateJoinAlias('site');
                $queryBuilder->leftJoin($rootAlias.'.site', $siteAlias);

                $siteCodeExpression = $this->createSiteCodeEndExpression($queryBuilder, $queryNameGenerator, $siteAlias, $siteCodeChunk, $parameters);
                $queryBuilder->andWhere($siteCodeExpression);
            } // Normal case: both chunks present -> site code ends with first chunk AND name contains second chunk
            else {
                $siteAlias = $queryNameGenerator->generateJoinAlias('site');
                $queryBuilder->leftJoin($rootAlias.'.site', $siteAlias);

                $siteCodeExpression = $this->createSiteCodeEndExpression($queryBuilder, $queryNameGenerator, $siteAlias, $siteCodeChunk, $parameters);
                $nameExpression = $this->createNameLikeExpression($queryBuilder, $queryNameGenerator, $rootAlias, $nameChunk, $parameters);

                $andWhere = $queryBuilder->expr()->andX()
                    ->add($siteCodeExpression)
                    ->add($nameExpression);

                $queryBuilder->andWhere($andWhere);
            }
        }

        $queryBuilder->setParameters($parameters);
    }

    private function createNameLikeExpression(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $rootAlias, string $value, ArrayCollection $parameters): string
    {
        $nameParameter = new Parameter(
            $queryNameGenerator->generateParameterName('name'),
            '%'.$value.'%'
        );

        $parameters->add($nameParameter);

        return $queryBuilder->expr()->like(
            "LOWER(unaccented($rootAlias.name))",
            'LOWER(unaccented(:'.$nameParameter->getName().'))'
        );
    }

    private function createSiteCodeEndExpression(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $siteAlias, string $value, ArrayCollection $parameters): string
    {
        $siteCodeEnd = strtoupper($value);

        $siteCodeParameter = new Parameter(
            $queryNameGenerator->generateParameterName('siteCode'),
            '%'.strtoupper($siteCodeEnd)
        );

        $parameters->add($siteCodeParameter);

        return $queryBuilder->expr()->like(
            "$siteAlias.code",
            ':'.$siteCodeParameter->getName()
        );
    }

    public function getDescription(string $resourceClass): array
    {
        return [
            'search' => [
                'property' => 'search',
                'type' => BuiltinType::string(),
                'required' => false,
                'description' => 'Search by name (case insensitive like) if single value, or by site code end AND name (both conditions must match) if value contains dot. Edge cases: ".name" searches only by name, "code." searches only by site code. Format: "siteCode.namePattern"',
                'openapi' => [
                    'example' => 'TO.fill 90',
                    'allowReserved' => false,
                    'explode' => false,
                ],
            ],
        ];
    }
}
