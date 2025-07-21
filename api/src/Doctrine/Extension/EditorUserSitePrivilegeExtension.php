<?php

namespace App\Doctrine\Extension;

use ApiPlatform\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use App\Entity\Auth\SiteUserPrivilege;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bundle\SecurityBundle\Security;

readonly class EditorUserSitePrivilegeExtension implements QueryCollectionExtensionInterface
{
    public function __construct(private Security $security)
    {
    }

    public function applyToCollection(
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $resourceClass,
        ?Operation $operation = null,
        array $context = [],
    ): void {
        $this->addWhere($queryBuilder, $queryNameGenerator, $resourceClass);
    }

    /**
     * Constrains query results to SiteUserPrivilege records where the site was createdBy the current request user.
     *
     * @param QueryBuilder                $queryBuilder       the query builder instance used to construct the query
     * @param QueryNameGeneratorInterface $queryNameGenerator generates unique aliases for SQL joins
     * @param string                      $resourceClass      the fully qualified class name of the entity resource being queried
     */
    private function addWhere(
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $resourceClass,
    ): void {
        if (
            SiteUserPrivilege::class !== $resourceClass
        ) {
            return;
        }

        if (
            // ROLE_ADMIN inherits ROLE_EDITOR so
            // lower roles and unauthenticated users are explicitly skipped
            // and eventually denied by the security layer
            $this->security->isGranted('ROLE_ADMIN')
            || !$this->security->isGranted('ROLE_EDITOR')
        ) {
            return;
        }

        $rootAlias = $queryBuilder->getRootAliases()[0];
        $joinSite = sprintf('%s.site', $rootAlias);
        $joinSiteAlias = $queryNameGenerator->generateJoinAlias('site');
        $queryBuilder
            ->innerJoin(
                $joinSite,
                $joinSiteAlias,
                Expr\Join::WITH,
                $queryBuilder->expr()->eq(
                    "$rootAlias.site",
                    "$joinSiteAlias.id"
                )
            )->andWhere(
                $queryBuilder->expr()->eq(
                    "$joinSiteAlias.createdBy",
                    ':user'
                )
            )->setParameter(
                'user',
                $this->security->getUser()->getId()
            );
    }
}
