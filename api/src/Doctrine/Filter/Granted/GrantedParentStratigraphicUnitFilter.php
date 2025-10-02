<?php

namespace App\Doctrine\Filter\Granted;

use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use App\Entity\Data\Pottery;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;

final class GrantedParentStratigraphicUnitFilter extends AbstractGrantedFilter
{
    private string $siteProperty;
    private string $stratigraphicUnitProperty;

    public function __construct(
        Security $security,
        ?ManagerRegistry $managerRegistry = null,
        ?LoggerInterface $logger = null,
        ?array $properties = ['granted'],
        ?NameConverterInterface $nameConverter = null,
        ?string $siteProperty = 'site',
        ?string $stratigraphicUnitProperty = 'stratigraphicUnit',
    ) {
        parent::__construct($security, $managerRegistry, $logger, $properties, $nameConverter);
        $this->siteProperty = $siteProperty;
        $this->stratigraphicUnitProperty = $stratigraphicUnitProperty;
    }

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
        $suAlias = $queryNameGenerator->generateJoinAlias($this->stratigraphicUnitProperty);
        $siteAlias = $queryNameGenerator->generateJoinAlias($this->siteProperty);
        $privilegeAlias = $queryNameGenerator->generateJoinAlias('privilege');
        $userParameterName = $queryNameGenerator->generateParameterName('user');

        $queryBuilder
            ->innerJoin("$rootAlias.{$this->stratigraphicUnitProperty}", $suAlias)
            ->innerJoin("$suAlias.{$this->siteProperty}", $siteAlias)
            ->innerJoin("$siteAlias.userPrivileges", $privilegeAlias)
            ->andWhere($queryBuilder->expr()->eq("$privilegeAlias.user", ":$userParameterName"))
            ->setParameter($userParameterName, $user->getId());
    }

    protected function getFilterDescription(): string
    {
        return 'Filter entries to only those from SU belonging to sites where the current user has privileges. If no user is authenticated, returns empty set.';
    }
}
