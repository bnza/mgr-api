<?php

namespace App\Doctrine\Filter\Granted;

use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use App\Entity\Data\Pottery;
use Doctrine\ORM\QueryBuilder;

final class GrantedPotteryFilter extends AbstractGrantedFilter
{
    protected function supports(string $resourceClass): bool
    {
        return Pottery::class === $resourceClass;
    }

    protected function applyGrantedFilter(
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $rootAlias,
        mixed $user,
    ): void {
        // Join with site and site_user_privileges table to filter only contexts from sites where user has privileges
        $suAlias = $queryNameGenerator->generateJoinAlias('stratigraphicUnit');
        $siteAlias = $queryNameGenerator->generateJoinAlias('site');
        $privilegeAlias = $queryNameGenerator->generateJoinAlias('privilege');
        $userParameterName = $queryNameGenerator->generateParameterName('user');

        $queryBuilder
            ->innerJoin("$rootAlias.stratigraphicUnit", $suAlias)
            ->innerJoin("$suAlias.site", $siteAlias)
            ->innerJoin("$siteAlias.userPrivileges", $privilegeAlias)
            ->andWhere($queryBuilder->expr()->eq("$privilegeAlias.user", ":$userParameterName"))
            ->setParameter($userParameterName, $user->getId());
    }

    protected function getFilterDescription(): string
    {
        return 'Filter contexts to only those from sites where the current user has privileges. If no user is authenticated, returns empty set.';
    }
}
