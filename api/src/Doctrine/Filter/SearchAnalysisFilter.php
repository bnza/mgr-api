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
use Symfony\Component\TypeInfo\TypeIdentifier;

final class SearchAnalysisFilter extends AbstractFilter
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
            // Single chunk: search in both type code and identifier
            $typeAlias = $queryNameGenerator->generateJoinAlias('type');
            $queryBuilder->leftJoin($rootAlias.'.type', $typeAlias);

            $typeCodeExpression = $this->createTypeCodeLikeExpression($queryBuilder, $queryNameGenerator, $typeAlias, $chunks[0], $parameters);
            $identifierExpression = $this->createIdentifierLikeExpression($queryBuilder, $queryNameGenerator, $rootAlias, $chunks[0], $parameters);

            $orWhere = $queryBuilder->expr()->orX()
                ->add($typeCodeExpression)
                ->add($identifierExpression);

            $queryBuilder->andWhere($orWhere);
        } else {
            // Two chunks: handle edge cases
            $typeCodeChunk = trim($chunks[0]);
            $identifierChunk = trim($chunks[1]);

            $typeAlias = $queryNameGenerator->generateJoinAlias('type');
            $queryBuilder->leftJoin($rootAlias.'.type', $typeAlias);

            // Edge case: empty type code chunk (e.g., ".identifier") -> only search by identifier
            if (empty($typeCodeChunk)) {
                $identifierExpression = $this->createIdentifierLikeExpression($queryBuilder, $queryNameGenerator, $rootAlias, $identifierChunk, $parameters);
                $queryBuilder->andWhere($identifierExpression);
            } // Edge case: empty identifier chunk (e.g., "CODE." or "CODE.  ") -> only search by type code
            elseif (empty($identifierChunk)) {
                $typeCodeExpression = $this->createTypeCodeLikeExpression($queryBuilder, $queryNameGenerator, $typeAlias, $typeCodeChunk, $parameters);
                $queryBuilder->andWhere($typeCodeExpression);
            } // Normal case: both chunks present -> type code contains first chunk AND identifier contains second chunk
            else {
                $typeCodeExpression = $this->createTypeCodeLikeExpression($queryBuilder, $queryNameGenerator, $typeAlias, $typeCodeChunk, $parameters);
                $identifierExpression = $this->createIdentifierLikeExpression($queryBuilder, $queryNameGenerator, $rootAlias, $identifierChunk, $parameters);

                $andWhere = $queryBuilder->expr()->andX()
                    ->add($typeCodeExpression)
                    ->add($identifierExpression);

                $queryBuilder->andWhere($andWhere);
            }
        }

        $queryBuilder->setParameters($parameters);
    }

    private function createTypeCodeLikeExpression(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $typeAlias, string $value, ArrayCollection $parameters): string
    {
        $typeCodeParameter = new Parameter(
            $queryNameGenerator->generateParameterName('typeCode'),
            '%'.$value.'%'
        );

        $parameters->add($typeCodeParameter);

        return $queryBuilder->expr()->like(
            "LOWER($typeAlias.code)",
            'LOWER(:'.$typeCodeParameter->getName().')'
        );
    }

    private function createIdentifierLikeExpression(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $rootAlias, string $value, ArrayCollection $parameters): string
    {
        $identifierParameter = new Parameter(
            $queryNameGenerator->generateParameterName('identifier'),
            '%'.$value.'%'
        );

        $parameters->add($identifierParameter);

        return $queryBuilder->expr()->like(
            "LOWER($rootAlias.identifier)",
            'LOWER(:'.$identifierParameter->getName().')'
        );
    }

    public function getDescription(string $resourceClass): array
    {
        return [
            'search' => [
                'property' => 'search',
                'type' => TypeIdentifier::STRING,
                'required' => false,
                'description' => 'Search by analysis type code OR identifier (case insensitive like) if single value, or by analysis type code AND identifier (both conditions must match) if value contains dot. Edge cases: ".identifier" searches only by identifier, "typeCode." searches only by type code. Format: "typeCode.identifier"',
                'openapi' => [
                    'example' => 'XRF.sample001',
                    'allowReserved' => false,
                    'explode' => false,
                ],
            ],
        ];
    }
}
