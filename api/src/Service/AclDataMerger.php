<?php

namespace App\Service;

use Symfony\Bundle\SecurityBundle\Security;

readonly class AclDataMerger
{
    public function __construct(private Security $security)
    {
    }

    public function hasAclContext(array $context): bool
    {
        return array_key_exists('groups', $context)
            && is_array($context['groups'])
            && array_reduce(
                $context['groups'],
                function ($acc, $group) {
                    $acc |= str_contains($group, ':acl:');

                    return $acc;
                },
                false
            );
    }

    public function merge(array $normalizedData, object $object): array
    {
        $normalizedData['_acl'] = [];
        $normalizedData['_acl']['canRead'] = $this->security->isGranted('read', $object);
        $normalizedData['_acl']['canUpdate'] = $this->security->isGranted('update', $object);
        $normalizedData['_acl']['canDelete'] = $this->security->isGranted('delete', $object);

        return $normalizedData;
    }
}
