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

final class SearchZooBoneFilter extends AbstractFilter
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

        // Split value using any non-word character and keep only the first two non-empty chunks
        $chunks = array_map('trim', preg_split('/\W+/', $value));
        $chunks = array_slice(array_filter($chunks, fn ($chunk) => !empty($chunk)), 0, 2);

        if (empty($chunks)) {
            return;
        }

        $expressions = [];

        foreach ($chunks as $chunk) {
            if (is_numeric($chunk)) {
                // Numeric chunk: match the end of CAST(ZooBone->id as STRING)
                $expressions[] = $this->createIdEndExpression($queryBuilder, $queryNameGenerator, $rootAlias, $chunk, $parameters);
            } else {
                // Non-numeric chunk: match the end of ZooBone->stratigraphicUnit->site->code
                $expressions[] = $this->createSiteCodeEndExpression($queryBuilder, $queryNameGenerator, $rootAlias, $chunk, $parameters);
            }
        }

        // Combine all expressions with AND
        if (1 === count($expressions)) {
            $queryBuilder->andWhere($expressions[0]);
        } else {
            $andWhere = $queryBuilder->expr()->andX();
            foreach ($expressions as $expression) {
                $andWhere->add($expression);
            }
            $queryBuilder->andWhere($andWhere);
        }

        $queryBuilder->setParameters($parameters);
    }

    private function createIdEndExpression(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $rootAlias, string $value, ArrayCollection $parameters): string
    {
        $idParameter = new Parameter(
            $queryNameGenerator->generateParameterName('id'),
            '%'.$value
        );

        $parameters->add($idParameter);

        return $queryBuilder->expr()->like(
            "CAST($rootAlias.id as STRING)",
            ':'.$idParameter->getName()
        );
    }

    private function createSiteCodeEndExpression(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $rootAlias, string $value, ArrayCollection $parameters): string
    {
        // Join stratigraphicUnit and then site
        $stratigraphicUnitAlias = $queryNameGenerator->generateJoinAlias('stratigraphicUnit');
        $siteAlias = $queryNameGenerator->generateJoinAlias('site');

        $queryBuilder->leftJoin($rootAlias.'.stratigraphicUnit', $stratigraphicUnitAlias);
        $queryBuilder->leftJoin($stratigraphicUnitAlias.'.site', $siteAlias);

        $siteCodeParameter = new Parameter(
            $queryNameGenerator->generateParameterName('siteCode'),
            '%'.strtoupper($value)
        );

        $parameters->add($siteCodeParameter);

        return $queryBuilder->expr()->like(
            "UPPER($siteAlias.code)",
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
                'description' => 'Search ZooBone records. Splits input by non-word characters and uses first two chunks. Numeric chunks match the end of ID (cast as string), non-numeric chunks match the end of site code. Multiple chunks are combined with AND.',
                'openapi' => [
                    'example' => 'MO 123',
                    'allowReserved' => false,
                    'explode' => false,
                ],
            ],
        ];
    }
}
