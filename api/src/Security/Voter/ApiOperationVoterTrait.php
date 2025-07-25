<?php

namespace App\Security\Voter;

trait ApiOperationVoterTrait
{
    public const string CREATE = 'create';
    public const string READ = 'read';
    public const string UPDATE = 'update';
    public const string DELETE = 'delete';

    protected function isAttributeSupported(string $attribute): bool
    {
        return in_array(
            $attribute,
            [
                self::CREATE,
                self::READ,
                self::UPDATE,
                self::DELETE,
            ]
        );
    }

    protected function isMutationOperation(string $attribute): bool
    {
        return in_array(
            $attribute,
            [
                self::CREATE,
                self::UPDATE,
                self::DELETE,
            ]
        );
    }
}
