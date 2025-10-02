<?php

namespace App\Doctrine\Filter\Granted;

use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use App\Entity\Data\Context;
use App\Entity\Data\Sample;
use App\Entity\Data\SedimentCore;
use App\Entity\Data\StratigraphicUnit;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;

final class GrantedParentSiteFilter extends AbstractGrantedFilter
{
    private string $siteProperty;

    public function __construct(
        Security $security,
        ?ManagerRegistry $managerRegistry = null,
        ?LoggerInterface $logger = null,
        ?array $properties = ['granted'],
        ?NameConverterInterface $nameConverter = null,
        ?string $siteProperty = 'site',
    ) {
        parent::__construct($security, $managerRegistry, $logger, $properties, $nameConverter);
        $this->siteProperty = $siteProperty;
    }

    protected function supports(string $resourceClass): bool
    {
        return in_array($resourceClass, [Context::class, Sample::class, SedimentCore::class, StratigraphicUnit::class]);
    }

    protected function applyGrantedFilter(
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $rootAlias,
        mixed $user,
    ): void {
        // Join with site and site_user_privileges table to filter only stratigraphic units from sites where user has privileges
        $siteAlias = $queryNameGenerator->generateJoinAlias($this->siteProperty);
        $privilegeAlias = $queryNameGenerator->generateJoinAlias('privilege');
        $userParameterName = $queryNameGenerator->generateParameterName('user');

        $queryBuilder
            ->innerJoin("$rootAlias.{$this->siteProperty}", $siteAlias)
            ->innerJoin("$siteAlias.userPrivileges", $privilegeAlias)
            ->andWhere($queryBuilder->expr()->eq("$privilegeAlias.user", ":$userParameterName"))
            ->setParameter($userParameterName, $user->getId());
    }

    protected function getFilterDescription(): string
    {
        return 'Filter sample to only those from sites where the current user has privileges. If no user is authenticated, returns empty set.';
    }
}
