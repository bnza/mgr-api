<?php

namespace App\Doctrine\Extension;

use ApiPlatform\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Doctrine\Orm\Extension\QueryItemExtensionInterface;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use App\Entity\Data\Analysis;
use App\Entity\Data\Pottery;
use App\Entity\Data\StratigraphicUnit;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bundle\SecurityBundle\Security;

readonly class PublicMediaObjectsCollectionExtension implements QueryCollectionExtensionInterface, QueryItemExtensionInterface
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

    public function applyToItem(
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $resourceClass,
        array $identifiers,
        ?Operation $operation = null,
        array $context = [],
    ): void {
        $this->addWhere($queryBuilder, $queryNameGenerator, $resourceClass);
    }

    private function addWhere(
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $resourceClass,
    ): void {
        // Only for item entities exposed with a ManyToMany through a join entity
        if (
            !in_array($resourceClass, [
                Pottery::class,
                Analysis::class,
                StratigraphicUnit::class,
            ], true)
            || $this->security->isGranted('IS_AUTHENTICATED_FULLY')
        ) {
            return;
        }

        $rootAlias = $queryBuilder->getRootAliases()[0];

        if (!$rootAlias) {
            return;
        }

        // Prefer reusing an existing join on "<rootAlias>.mediaObjects"
        $mediaObjectsAlias = $this->findExistingMediaObjectsJoinAlias($queryBuilder, $rootAlias);

        // If not present, also trigger when WHERE contains "<rootAlias>.mediaObjects IS NOT EMPTY"
        if (null === $mediaObjectsAlias && !$this->whereHasIsNotEmptyOnMediaObjects($queryBuilder, $rootAlias)) {
            // Neither a join nor the specific WHERE clause is present → do nothing
            return;
        }

        // If we reached here without an existing join alias, add it now (because IS NOT EMPTY does not require a join)
        if (null === $mediaObjectsAlias) {
            $mediaObjectsAlias = $queryNameGenerator->generateJoinAlias('media_objects');
            $queryBuilder->innerJoin(sprintf('%s.mediaObjects', $rootAlias), $mediaObjectsAlias);
        }

        // Hop to MediaObject and enforce public=true
        $mediaAlias = $queryNameGenerator->generateJoinAlias('media_object');
        $queryBuilder->innerJoin(sprintf('%s.mediaObject', $mediaObjectsAlias), $mediaAlias);
        $queryBuilder->andWhere(sprintf('%s.public = true', $mediaAlias));
    }

    private function findExistingMediaObjectsJoinAlias(QueryBuilder $qb, string $rootAlias): ?string
    {
        $joins = $qb->getDQLPart('join');
        if (!isset($joins[$rootAlias])) {
            return null;
        }

        /** @var Join $join */
        foreach ($joins[$rootAlias] as $join) {
            // Looking specifically for: "<rootAlias>.mediaObjects"
            if ($join->getJoin() === sprintf('%s.mediaObjects', $rootAlias)) {
                // Reuse its alias to continue the hop to MediaObject
                return $join->getAlias();
            }
        }

        return null;
    }

    private function whereHasIsNotEmptyOnMediaObjects(QueryBuilder $qb, string $rootAlias): bool
    {
        $where = $qb->getDQLPart('where');
        if (null === $where) {
            return false;
        }

        // Simple string scan is sufficient for this specific predicate
        $needle = sprintf('%s.mediaObjects IS NOT EMPTY', $rootAlias);

        return false !== stripos((string) $where, $needle);
    }
}
