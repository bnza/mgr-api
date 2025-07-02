<?php

namespace App\Tests\Functional\Api;

trait ApiTestProviderTrait
{
    public static function nonAdminUserProvider(): array
    {
        return [
            'user_base' => ['user_base'],
            'user_editor' => ['user_editor'],
            'user_geo' => ['user_geo'],
        ];
    }

    public static function editorUserProvider(): array
    {
        return [
            'user_admin' => ['user_admin'],
            'user_editor' => ['user_editor'],
        ];
    }

    public static function nonEditorUserProvider(): array
    {
        return [
            'user_base' => ['user_base'],
            'user_geo' => ['user_geo'],
        ];
    }
}
