<?php

namespace App\Service;

use App\Service\CollectionVoter\CollectionVoter;
use Symfony\Bundle\SecurityBundle\Security;

readonly class AclDataMerger
{
    public function __construct(
        private Security $security,
        private CollectionVoter $collectionVoter,
    ) {
    }

    public function hasAclContext(array $context): bool
    {
        return array_key_exists('groups', $context)
            && is_array($context['groups'])
            && array_any(
                $context['groups'],
                function ($group) {
                    return str_contains($group, ':acl:');
                },
            );
    }

    public function mergeItem(array $normalizedData, object $object): array
    {
        $normalizedData['_acl'] = [];
        $normalizedData['_acl']['canRead'] = $this->security->isGranted('read', $object);
        $normalizedData['_acl']['canUpdate'] = $this->security->isGranted('update', $object);
        $normalizedData['_acl']['canDelete'] = $this->security->isGranted('delete', $object);

        return $normalizedData;
    }

    /**
     * Merges ACL data for a collection response.
     * Adds only collection-level permissions like `canCreate`.
     */
    public function mergeCollection(array $normalizedData, array $context): array
    {
        $normalizedData['_acl'] = $normalizedData['_acl'] ?? [];
        // Collection-level read is implied if the collection was returned
        $normalizedData['_acl']['canCreate'] = $this->collectionVoter->vote($context);

        return $normalizedData;
    }
}
