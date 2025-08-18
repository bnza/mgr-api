<?php

namespace App\Util;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessor;

/**
 * Synchronizes a one-to-many relationship between a persisted collection of join entities
 * and an incoming array of associated entities, ensuring consistency between the two.
 */
class EntityOneToManyRelationshipSynchronizer
{
    private PropertyAccessor $propertyAccessor;

    public function __construct(
        private Collection|ArrayCollection $persistedCollection,
        private readonly string $joinEntityClass,
        private readonly string $leftPropertyName,
        private readonly string $rightPropertyName,
    ) {
    }

    /**
     * Synchronizes the relationship between the owner entity and the incoming entities by updating the persisted collection.
     *
     * The method ensures that the persisted collection reflects the desired state described by the incoming entities:
     * - Entities present in the persisted collection but not in the incoming array are removed from the relationship.
     * - Entities present in the incoming array but not in the persisted collection are added to the relationship.
     *
     * @param array  $incomingEntities the incoming array of entities to synchronize the relationship with
     * @param object $ownerEntity      the owner entity that establishes the relationship
     */
    public function synchronize(array $incomingEntities, object $ownerEntity): void
    {
        $persistedEntitiesMap = $this->getPersistedEntitiesMap();

        $incomingEntitiesMap = $this->getIncomingEntitiesMap($incomingEntities);

        // Find entities that exist in persisted collection but not in incoming array
        // These are the entities that need to be deleted from the relationship
        $deletedJoinEntities = array_diff_key($persistedEntitiesMap, $incomingEntitiesMap);

        foreach ($deletedJoinEntities as $deletedJoinEntity) {
            $this->persistedCollection->removeElement($deletedJoinEntity);
        }

        // Find decorations that exist in incoming array but not in database
        // These need to be added to the relationship
        $addedEntities = array_diff_key($incomingEntitiesMap, $persistedEntitiesMap);

        $propertyAccessor = $this->getPropertyAccessor();

        foreach ($addedEntities as $addedEntity) {
            $joinEntity = new $this->joinEntityClass();
            $propertyAccessor->setValue($joinEntity, $this->leftPropertyName, $ownerEntity);
            $propertyAccessor->setValue($joinEntity, $this->rightPropertyName, $addedEntity);
            $this->persistedCollection->add($joinEntity);
        }
    }

    private function getPropertyAccessor(): PropertyAccessor
    {
        if (!isset($this->propertyAccessor)) {
            $this->propertyAccessor = PropertyAccess::createPropertyAccessor();
        }

        return $this->propertyAccessor;
    }

    /**
     * Converts the persisted collection of join entities into an associative array of right entities indexed by entity ID.
     * Same shape of the array as the incoming array of entities.
     *
     * @return array the map of right entity IDs to their associated join entities
     */
    private function getPersistedEntitiesMap(): array
    {
        $persistedEntitiesMap = [];
        $propertyAccessor = $this->getPropertyAccessor();

        foreach ($this->persistedCollection as $joinEntity) {
            $rightEntity = $propertyAccessor->getValue($joinEntity, $this->rightPropertyName);
            $rightEntityId = $propertyAccessor->getValue($rightEntity, 'id');
            $persistedEntitiesMap[$rightEntityId] = $joinEntity; // Store the join entity, not the right entity
        }

        return $persistedEntitiesMap;
    }

    /**
     * Converts an array of incoming (right) entities into an associative array indexed by entity ID.
     *
     * @param array $incomingEntities the array of incoming entities to be processed
     *
     * @return array an associative array where the keys are entity IDs and the values are the corresponding entities
     */
    private function getIncomingEntitiesMap(array $incomingEntities): array
    {
        $propertyAccessor = $this->getPropertyAccessor();

        $indexedIncomingEntities = [];
        foreach ($incomingEntities as $entity) {
            $indexedIncomingEntities[$propertyAccessor->getValue($entity, 'id')] = $entity;
        }

        return $indexedIncomingEntities;
    }
}
