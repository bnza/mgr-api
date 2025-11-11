<?php

namespace App\Doctrine\Filter\Granted;

use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use App\Entity\Data\Join\Analysis\AnalysisBotanyCharcoal;
use App\Entity\Data\Join\Analysis\AnalysisBotanySeed;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;

final class GrantedParentAnalysisSubjectFilter extends AbstractGrantedFilter
{
    private string $siteProperty;
    private string $stratigraphicUnitProperty;

    private string $subjectProperty;

    public function __construct(
        Security $security,
        ?ManagerRegistry $managerRegistry = null,
        ?LoggerInterface $logger = null,
        ?array $properties = ['granted'],
        ?NameConverterInterface $nameConverter = null,
        ?string $siteProperty = 'site',
        ?string $stratigraphicUnitProperty = 'stratigraphicUnit',
        ?string $subjectProperty = 'subject',
    ) {
        parent::__construct($security, $managerRegistry, $logger, $properties, $nameConverter);
        $this->siteProperty = $siteProperty;
        $this->stratigraphicUnitProperty = $stratigraphicUnitProperty;
        $this->subjectProperty = $subjectProperty;
    }

    protected function supports(string $resourceClass): bool
    {
        return in_array($resourceClass, [AnalysisBotanyCharcoal::class, AnalysisBotanySeed::class]);
    }

    protected function applyGrantedFilter(
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $rootAlias,
        mixed $user,
    ): void {
        // Join with site and site_user_privileges table to filter only contexts from sites where user has privileges
        $subjectAlias = $queryNameGenerator->generateJoinAlias($this->subjectProperty);
        $suAlias = $queryNameGenerator->generateJoinAlias($this->stratigraphicUnitProperty);
        $siteAlias = $queryNameGenerator->generateJoinAlias($this->siteProperty);
        $privilegeAlias = $queryNameGenerator->generateJoinAlias('privilege');
        $userParameterName = $queryNameGenerator->generateParameterName('user');

        $queryBuilder
            ->innerJoin("$rootAlias.{$this->subjectProperty}", $subjectAlias)
            ->innerJoin("$subjectAlias.{$this->stratigraphicUnitProperty}", $suAlias)
            ->innerJoin("$suAlias.{$this->siteProperty}", $siteAlias)
            ->innerJoin("$siteAlias.userPrivileges", $privilegeAlias)
            ->andWhere($queryBuilder->expr()->eq("$privilegeAlias.user", ":$userParameterName"))
            ->setParameter($userParameterName, $user->getId());
    }

    protected function getFilterDescription(): string
    {
        return 'Filter entries to only those from subjets belonging to SU belonging to sites where the current user has privileges. If no user is authenticated, returns empty set.';
    }
}
