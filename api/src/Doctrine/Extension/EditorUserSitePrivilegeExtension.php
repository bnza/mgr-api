<?php

namespace App\Doctrine\Extension;

use ApiPlatform\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Doctrine\Orm\Extension\QueryItemExtensionInterface;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use App\Entity\Auth\SiteUserPrivilege;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bundle\SecurityBundle\Security;

readonly class EditorUserSitePrivilegeExtension implements QueryCollectionExtensionInterface, QueryItemExtensionInterface
{

    public function __construct(private Security $security)
    {
    }

    public function applyToCollection(
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $resourceClass,
        ?Operation $operation = null,
        array $context = []
    ): void {
        $this->addWhere($queryBuilder, $queryNameGenerator, $resourceClass);
    }

    public function applyToItem(
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $resourceClass,
        array $identifiers,
        ?Operation $operation = null,
        array $context = []
    ): void {
        $this->addWhere($queryBuilder, $queryNameGenerator, $resourceClass);
    }

    /**
     * Constrains query results to SiteUserPrivilege records where the site was createdBy the current request user.
     *
     * @param QueryBuilder $queryBuilder The query builder instance used to construct the query.
     * @param QueryNameGeneratorInterface $queryNameGenerator Generates unique aliases for SQL joins.
     * @param string $resourceClass The fully qualified class name of the entity resource being queried.
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
            $this->security->isGranted('ROLE_ADMIN')
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
                    ":user"
                )
            )->setParameter(
                'user',
                $this->security->getUser()->getId()
            );
    }
}
