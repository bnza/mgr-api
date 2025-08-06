<?php

namespace App\Doctrine\Filter\Granted;

use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use App\Entity\Data\StratigraphicUnit;
use Doctrine\ORM\QueryBuilder;

final class GrantedStratigraphicUnitFilter extends AbstractGrantedFilter
{
    protected function supports(string $resourceClass): bool
    {
        return StratigraphicUnit::class === $resourceClass;
    }

    protected function applyGrantedFilter(
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $rootAlias,
        mixed $user,
    ): void {
        // Join with site and site_user_privileges table to filter only stratigraphic units from sites where user has privileges
        $siteAlias = $queryNameGenerator->generateJoinAlias('site');
        $privilegeAlias = $queryNameGenerator->generateJoinAlias('privilege');
        $userParameterName = $queryNameGenerator->generateParameterName('user');

        $queryBuilder
            ->innerJoin("$rootAlias.site", $siteAlias)
            ->innerJoin("$siteAlias.userPrivileges", $privilegeAlias)
            ->andWhere($queryBuilder->expr()->eq("$privilegeAlias.user", ":$userParameterName"))
            ->setParameter($userParameterName, $user->getId());
    }

    protected function getFilterDescription(): string
    {
        return 'Filter stratigraphic units to only those from sites where the current user has privileges. If no user is authenticated, returns empty set.';
    }
}
