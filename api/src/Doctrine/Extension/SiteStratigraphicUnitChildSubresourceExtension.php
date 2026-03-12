<?php

namespace App\Doctrine\Extension;

use ApiPlatform\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use App\Entity\Data\Botany\Charcoal;
use App\Entity\Data\Botany\Seed;
use App\Entity\Data\Individual;
use App\Entity\Data\MicrostratigraphicUnit;
use App\Entity\Data\Pottery;
use App\Entity\Data\Zoo\Bone;
use App\Entity\Data\Zoo\Tooth;
use Doctrine\ORM\QueryBuilder;

class SiteStratigraphicUnitChildSubresourceExtension implements QueryCollectionExtensionInterface
{
    private const SUPPORTED_RESOURCES = [
        Charcoal::class => '/data/archaeological_sites/{parentId}/botany/charcoals',
        Seed::class => '/data/archaeological_sites/{parentId}/botany/seeds',
        Individual::class => '/data/archaeological_sites/{parentId}/individuals',
        MicrostratigraphicUnit::class => '/data/archaeological_sites/{parentId}/microstratigraphic_units',
        Pottery::class => '/data/archaeological_sites/{parentId}/potteries',
        Bone::class => '/data/archaeological_sites/{parentId}/zoo/bones',
        Tooth::class => '/data/archaeological_sites/{parentId}/zoo/teeth',
    ];

    public function applyToCollection(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, ?Operation $operation = null, array $context = []): void
    {
        $expectedTemplate = self::SUPPORTED_RESOURCES[$resourceClass] ?? null;

        if (!$expectedTemplate || $expectedTemplate !== $operation?->getUriTemplate()) {
            return;
        }

        $parentId = $context['uri_variables']['parentId'] ?? null;

        if (!$parentId) {
            return;
        }

        $rootAlias = $queryBuilder->getRootAliases()[0];
        $suAlias = $queryNameGenerator->generateJoinAlias('stratigraphicUnit');

        $queryBuilder
            ->join(sprintf('%s.stratigraphicUnit', $rootAlias), $suAlias)
            ->andWhere(sprintf('%s.site = :siteId', $suAlias))
            ->setParameter('siteId', $parentId);
    }
}
