<?php

namespace App\Doctrine\Filter;

use ApiPlatform\Doctrine\Orm\Filter\AbstractFilter;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use App\Entity\Data\Site;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\PropertyInfo\Type;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;

final class GrantedSiteFilter extends AbstractFilter
{
    public function __construct(
        private Security $security,
        ?ManagerRegistry $managerRegistry = null,
        ?LoggerInterface $logger = null,
        ?array $properties = ['granted'],
        ?NameConverterInterface $nameConverter = null,
    ) {
        parent::__construct($managerRegistry, $logger, $properties, $nameConverter);
    }

    protected function filterProperty(string $property, $value, QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, ?Operation $operation = null, array $context = []): void
    {
        // Only handle the 'granted' property
        if ('granted' !== $property) {
            return;
        }

        // Only apply to Site entities
        if (Site::class !== $resourceClass) {
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

        // Join with site_user_privileges table to filter only sites where user has privileges
        $privilegeAlias = $queryNameGenerator->generateJoinAlias('privilege');
        $userParameterName = $queryNameGenerator->generateParameterName('user');

        $queryBuilder
            ->innerJoin("$rootAlias.userPrivileges", $privilegeAlias)
            ->andWhere($queryBuilder->expr()->eq("$privilegeAlias.user", ":$userParameterName"))
            ->setParameter($userParameterName, $user->getId());
    }

    public function getDescription(string $resourceClass): array
    {
        if (Site::class !== $resourceClass) {
            return [];
        }

        return [
            'granted' => [
                'property' => 'granted',
                'type' => Type::BUILTIN_TYPE_BOOL,
                'required' => false,
                'description' => 'Filter sites to only those where the current user has privileges. If no user is authenticated, returns empty set.',
                'openapi' => [
                    'example' => true,
                    'allowReserved' => false,
                    'explode' => false,
                ],
            ],
        ];
    }
}
