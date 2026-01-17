<?php

namespace App\Doctrine\Extension;

use ApiPlatform\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use App\Entity\Data\Join\Analysis\AnalysisSampleMicrostratigraphy;
use Doctrine\ORM\QueryBuilder;

class AnalysisSampleMicrostratigraphyStratigraphicUnitSubresourceExtension implements QueryCollectionExtensionInterface
{
    public function applyToCollection(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, ?Operation $operation = null, array $context = []): void
    {
        // Only apply if it's our specific resource and our specific URI
        if (AnalysisSampleMicrostratigraphy::class !== $resourceClass
            || '/stratigraphic_units/{parentId}/analyses/samples/microstratigraphy' !== $operation?->getUriTemplate()) {
            return;
        }

        $uriVars = $operation->getUriVariables();
        $parentId = ($uriVars['parentId'] ?? null) ?: ($context['uri_variables']['parentId'] ?? null);

        if (!$parentId) {
            return;
        }

        $sampleAlias = $queryNameGenerator->generateJoinAlias('sample');
        $ssuAlias = $queryNameGenerator->generateJoinAlias('ssu');

        $queryBuilder
            ->join(sprintf('%s.subject', $queryBuilder->getRootAliases()[0]), $sampleAlias)
            ->join(sprintf('%s.sampleStratigraphicUnits', $sampleAlias), $ssuAlias)
            ->andWhere(sprintf('%s.stratigraphicUnit = :suId', $ssuAlias))
            ->setParameter('suId', $parentId);
    }
}
