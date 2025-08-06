<?php

namespace App\Doctrine\Filter\Granted;

use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use App\Entity\Data\Site;
use Doctrine\ORM\QueryBuilder;

final class GrantedSiteFilter extends AbstractGrantedFilter
{
    protected function supports(string $resourceClass): bool
    {
        return Site::class === $resourceClass;
    }

    protected function applyGrantedFilter(
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $rootAlias,
        mixed $user,
    ): void {
        // Join with site_user_privileges table to filter only sites where user has privileges
        $privilegeAlias = $queryNameGenerator->generateJoinAlias('privilege');
        $userParameterName = $queryNameGenerator->generateParameterName('user');

        $queryBuilder
            ->innerJoin("$rootAlias.userPrivileges", $privilegeAlias)
            ->andWhere($queryBuilder->expr()->eq("$privilegeAlias.user", ":$userParameterName"))
            ->setParameter($userParameterName, $user->getId());
    }

    protected function getFilterDescription(): string
    {
        return 'Filter sites to only those where the current user has privileges. If no user is authenticated, returns empty set.';
    }
}
