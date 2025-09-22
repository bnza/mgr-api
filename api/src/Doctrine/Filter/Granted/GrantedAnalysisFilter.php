<?php

namespace App\Doctrine\Filter\Granted;

use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use App\Entity\Data\Analysis;
use Doctrine\ORM\QueryBuilder;

final class GrantedAnalysisFilter extends AbstractGrantedFilter
{
    protected function supports(string $resourceClass): bool
    {
        return Analysis::class === $resourceClass;
    }

    protected function applyGrantedFilter(
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $rootAlias,
        mixed $user,
    ): void {
        // Filter only analyses where current user matches the createdBy value
        $userParameterName = $queryNameGenerator->generateParameterName('createdByUser');

        $queryBuilder
            ->andWhere($queryBuilder->expr()->eq("$rootAlias.createdBy", ":$userParameterName"))
            ->setParameter($userParameterName, $user->getId());
    }

    protected function getFilterDescription(): string
    {
        return 'Filter analyses to only those created by the current user. If no user is authenticated, returns empty set.';
    }
}
