<?php

namespace App\Doctrine\Filter\Granted;

use ApiPlatform\Doctrine\Orm\Filter\AbstractFilter;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;
use Symfony\Component\TypeInfo\Type\BuiltinType;

abstract class AbstractGrantedFilter extends AbstractFilter
{
    public function __construct(
        private readonly Security $security,
        ?ManagerRegistry $managerRegistry = null,
        ?LoggerInterface $logger = null,
        ?array $properties = ['granted'],
        ?NameConverterInterface $nameConverter = null,
    ) {
        parent::__construct($managerRegistry, $logger, $properties, $nameConverter);
    }

    final protected function filterProperty(string $property, $value, QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, ?Operation $operation = null, array $context = []): void
    {
        // Only handle the 'granted' property
        if ('granted' !== $property) {
            return;
        }

        // Only apply to supported entities
        if (!$this->supports($resourceClass)) {
            return;
        }

        // If no value provided or value is false, don't apply filter
        if (empty($value) || false === filter_var($value, FILTER_VALIDATE_BOOLEAN)) {
            return;
        }

        $rootAlias = $queryBuilder->getRootAliases()[0];

        // Check if user is authenticated
        if (!$this->security->isGranted('IS_AUTHENTICATED_FULLY')) {
            // If no user, return empty set by adding impossible condition
            $queryBuilder->andWhere($queryBuilder->expr()->isNull("$rootAlias.id"));

            return;
        }

        if ($this->security->isGranted('ROLE_ADMIN')) {
            return;
        }

        $user = $this->security->getUser();

        if (!$user) {
            // If no user, return empty set by adding impossible condition
            $queryBuilder->andWhere($queryBuilder->expr()->isNull("$rootAlias.id"));

            return;
        }

        // Apply specific filtering logic for the resource
        $this->applyGrantedFilter($queryBuilder, $queryNameGenerator, $rootAlias, $user);
    }

    final public function getDescription(string $resourceClass): array
    {
        if (!$this->supports($resourceClass)) {
            return [];
        }

        return [
            'granted' => [
                'property' => 'granted',
                'type' => BuiltinType::string(),
                'required' => false,
                'description' => $this->getFilterDescription(),
                'openapi' => [
                    'example' => true,
                    'allowReserved' => false,
                    'explode' => false,
                ],
            ],
        ];
    }

    /**
     * Check if this filter supports the given resource class.
     */
    abstract protected function supports(string $resourceClass): bool;

    /**
     * Apply the specific filtering logic for the resource.
     */
    abstract protected function applyGrantedFilter(
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $rootAlias,
        mixed $user,
    ): void;

    /**
     * Get the description for the filter.
     */
    abstract protected function getFilterDescription(): string;
}
