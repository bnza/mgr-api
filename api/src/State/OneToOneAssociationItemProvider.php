<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Override default item provider since Doctrine deos not hanlde correctlu OneToOne relations using field names (eg: analysis)
 * different from the column name (eg: 'id')
 * Mostly used for AbsDatingAnalysisJoin sublasses.
 */
readonly class OneToOneAssociationItemProvider implements ProviderInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        $id = $uriVariables['id'] ?? null;

        if (!$id) {
            return null;
        }

        $resourceClass = $operation->getClass();

        if (!$resourceClass) {
            return null;
        }

        return $this->entityManager->find($resourceClass, $id);
    }
}
