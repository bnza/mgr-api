<?php

namespace App\Tests\Functional\Api\Auth;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class ApiAuthTest extends ApiTestCase
{

    public const string USER_BASE = 'user_base@example.com';
    public const string USER_EDITOR = 'user_editor@example.com';
    public const string USER_ADMIN = 'user_admin@example.com';
    public const USER_GEO = 'user_geo@example.com';

    private ?ParameterBagInterface $parameterBag = null;

    protected function setUp(): void
    {
        parent::setUp();

        static::$alwaysBootKernel = false;

        $this->parameterBag = self::getContainer()->get(ParameterBagInterface::class);
    }

    protected function tearDown(): void
    {
        $this->parameterBag = null;
        parent::tearDown();
    }


    public static function userCredentialsProvider(): array
    {
        return [
            'user_base' => ['user_base'],
            'user_editor' => ['user_editor'],
            'user_admin' => ['user_admin'],
            'user_geo' => ['user_geo'],
        ];
    }

    #[DataProvider('userCredentialsProvider')]
    public function testSuccessfulJwtAuthentication(string $username)
    {
        $client = self::createClient();
        $response = $client->request('POST', '/api/login', [
            'json' => [
                'email' => "$username@example.com",
                'password' => $this->parameterBag->get("app.alice.parameters.{$username}_pw"),
            ],
        ]);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertResponseHeaderSame('content-type', 'application/json');
        $this->assertJsonContains([
            'token' => $response->toArray()['token'],
        ]);
    }

    public function testCurrentUserResource()
    {
        $client = self::createClient();
        $response = $client->request('GET', '/api/users/me');

        $this->assertSame(401, $response->getStatusCode());
        $response = $client->request('POST', '/api/login', [
            'json' => [
                'email' => "user_base@example.com",
                'password' => $this->parameterBag->get("app.alice.parameters.user_base_pw"),
            ],
        ]);
        $this->assertSame(200, $response->getStatusCode());
    }
}
