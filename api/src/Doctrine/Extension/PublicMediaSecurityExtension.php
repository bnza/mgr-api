<?php

namespace App\Doctrine\Extension;

use ApiPlatform\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Doctrine\Orm\Extension\QueryItemExtensionInterface;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use App\Entity\Data\Join\MediaObject\BaseMediaObjectJoin;
use App\Entity\Data\MediaObject;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * Ensures that unauthenticated users only see public media objects.
 * This covers:
 * 1. The MediaObject resource itself.
 * 2. Join entities linking other resources to MediaObject (subclasses of BaseMediaObjectJoin).
 * 3. Parent resources containing a 'mediaObjects' collection of joins.
 *
 * This extension handles high-level API response structure (preventing orphans).
 * For a low-level database-wide safety net, see PublicMediaObjectSqlFilter which
 * is enabled by the PublicMediaFilterListener.
 */
readonly class PublicMediaSecurityExtension implements QueryCollectionExtensionInterface, QueryItemExtensionInterface
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
        if ($this->security->isGranted('IS_AUTHENTICATED_FULLY')) {
            return;
        }

        $rootAlias = $queryBuilder->getRootAliases()[0];

        // 1. Direct MediaObject resource filtering
        if (MediaObject::class === $resourceClass) {
            $queryBuilder->andWhere(sprintf('%s.public = true', $rootAlias));

            return;
        }

        // 2. MediaObject join entities filtering (subclasses of BaseMediaObjectJoin)
        if (is_subclass_of($resourceClass, BaseMediaObjectJoin::class)) {
            $mediaObjectAlias = $queryNameGenerator->generateJoinAlias('media_object');
            $queryBuilder->innerJoin(sprintf('%s.mediaObject', $rootAlias), $mediaObjectAlias);
            $queryBuilder->andWhere(sprintf('%s.public = true', $mediaObjectAlias));

            return;
        }

        // 3. Parent entities filtering (entities that have a 'mediaObjects' collection of joins)
        $em = $queryBuilder->getEntityManager();
        $metadata = $em->getClassMetadata($resourceClass);
        if (!$metadata->hasAssociation('mediaObjects')) {
            return;
        }

        $targetEntity = $metadata->getAssociationTargetClass('mediaObjects');
        if (!is_subclass_of($targetEntity, BaseMediaObjectJoin::class)) {
            return;
        }

        // Apply only if there is already a join on "mediaObjects" or IS NOT EMPTY check
        $mediaObjectsAlias = $this->findExistingMediaObjectsJoinAlias($queryBuilder, $rootAlias);
        if (null === $mediaObjectsAlias && !$this->whereHasIsNotEmptyOnMediaObjects($queryBuilder, $rootAlias)) {
            return;
        }

        if (null === $mediaObjectsAlias) {
            $mediaObjectsAlias = $queryNameGenerator->generateJoinAlias('media_objects');
            $queryBuilder->innerJoin(sprintf('%s.mediaObjects', $rootAlias), $mediaObjectsAlias);
        }

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
            if ($join->getJoin() === sprintf('%s.mediaObjects', $rootAlias)) {
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

        $needle = sprintf('%s.mediaObjects IS NOT EMPTY', $rootAlias);

        return false !== stripos((string) $where, $needle);
    }
}
