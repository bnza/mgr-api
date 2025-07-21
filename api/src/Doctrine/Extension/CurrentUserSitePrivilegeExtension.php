<?php

namespace App\Doctrine\Extension;

use ApiPlatform\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use App\Entity\Auth\SiteUserPrivilege;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bundle\SecurityBundle\Security;

readonly class CurrentUserSitePrivilegeExtension implements QueryCollectionExtensionInterface
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
        $this->addWhere($queryBuilder, $queryNameGenerator, $resourceClass, $operation);
    }

    /**
     * Constrains query results to SiteUserPrivilege records where the user is the current request user.
     *
     * @param QueryBuilder                $queryBuilder       the QueryBuilder instance to modify
     * @param QueryNameGeneratorInterface $queryNameGenerator used to generate unique parameter names for the query
     * @param string                      $resourceClass      the class of the resource being queried
     */
    private function addWhere(
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $resourceClass,
        ?Operation $operation = null,
    ): void {
        if (
            SiteUserPrivilege::class !== $resourceClass
            || '/users/me/site_user_privileges' !== $operation?->getUriTemplate()
        ) {
            return;
        }

        if (!$this->security->isGranted('IS_AUTHENTICATED_FULLY')) {
            return;
        }

        $user = $this->security->getUser();

        $rootAlias = $queryBuilder->getRootAliases()[0];
        $userColumn = sprintf('%s.user', $rootAlias);
        $parameterName = $queryNameGenerator->generateParameterName('user');
        $queryBuilder->andWhere(
            $queryBuilder->expr()->eq(
                $userColumn,
                ":$parameterName"
            )
        )->setParameter($parameterName, $user->getId());
    }
}
