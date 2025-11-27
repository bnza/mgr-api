<?php

namespace App\Doctrine\Extension;

use ApiPlatform\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Doctrine\Orm\Extension\QueryItemExtensionInterface;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use App\Entity\Data\Join\MediaObject\MediaObjectAnalysis;
use App\Entity\Data\Join\MediaObject\MediaObjectPottery;
use App\Entity\Data\Join\MediaObject\MediaObjectStratigraphicUnit;
use App\Entity\Data\MediaObject;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bundle\SecurityBundle\Security;

readonly class PublicMediaObjectJoinExtension implements QueryCollectionExtensionInterface, QueryItemExtensionInterface
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
        if (
            !in_array($resourceClass, [MediaObjectAnalysis::class, MediaObjectPottery::class, MediaObjectStratigraphicUnit::class])
            || $this->security->isGranted('IS_AUTHENTICATED_FULLY')
        ) {
            return;
        }

        $rootAlias = $queryBuilder->getRootAliases()[0];

        $mediaObjectAlias = $queryNameGenerator->generateJoinAlias('media_object');
        $queryBuilder->innerJoin(
            sprintf('%s.mediaObject', $rootAlias),
            $mediaObjectAlias
        );
        $queryBuilder->andWhere(sprintf('%s.public = true', $mediaObjectAlias));
        //
        //        $queryBuilder->andWhere(sprintf('%s.public = true', $mediaObjectAlias));
        //        $rootAlias = $queryBuilder->getRootAliases()[0];
        //
        //        $mediaObjectsJoinAlias = $queryNameGenerator->generateJoinAlias('media_objects');
        //        $queryBuilder->innerJoin(
        //            sprintf('%s.mediaObjects', $rootAlias),
        //            $mediaObjectsJoinAlias
        //        );
        //
        //        $mediaObjectAlias = $queryNameGenerator->generateJoinAlias('media_object');
        //        $queryBuilder->innerJoin(
        //            sprintf('%s.mediaObject', $mediaObjectsJoinAlias),
        //            $mediaObjectAlias
        //        );
        //
        //        $queryBuilder->andWhere(sprintf('%s.public = true', $mediaObjectAlias));
    }
}
