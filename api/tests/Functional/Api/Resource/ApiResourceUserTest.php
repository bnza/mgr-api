<?php

namespace App\Tests\Functional\Api\Resource;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class ApiResourceUserTest extends ApiTestCase
{
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
            'user_geo' => ['user_geo'],
        ];
    }

    public function testGetCollectionIsDeniedForAnonymousUser()
    {
        $client = self::createClient();
        $users = $this->getUsers();
        $loginResponse = $client->request('GET', $users[0]['@id']);
        $this->assertSame(401, $loginResponse->getStatusCode());
    }

    public function testGetItemIsDeniedForAnonymousUser()
    {
        $client = self::createClient();
        $loginResponse = $client->request('GET', '/api/users');
        $this->assertSame(401, $loginResponse->getStatusCode());
    }

    #[DataProvider('userCredentialsProvider')]
    public function testGetCollectionIsDeniedForNonAdminUser(string $username): void
    {
        $client = self::createClient();

        $loginResponse = $client->request('POST', '/api/login', [
            'json' => [
                'email' => "$username@example.com",
                'password' => $this->parameterBag->get("app.alice.parameters.{$username}_pw"),
            ],
        ]);

        $this->assertSame(200, $loginResponse->getStatusCode());
        $token = $loginResponse->toArray()['token'];

        $userResponse = $client->request('GET', '/api/users', [
            'headers' => [
                'Authorization' => "Bearer $token",
            ],
        ]);

        $this->assertSame(403, $userResponse->getStatusCode());
    }

    #[DataProvider('userCredentialsProvider')]
    public function testGetItemIsDeniedForNonAdminUser(string $username): void
    {

        $client = self::createClient();

        $loginResponse = $client->request('POST', '/api/login', [
            'json' => [
                'email' => "$username@example.com",
                'password' => $this->parameterBag->get("app.alice.parameters.{$username}_pw"),
            ],
        ]);

        $this->assertResponseStatusCodeSame(200);
        $token = $loginResponse->toArray()['token'];

        $users = $this->getUsers();

        $userResponse = $client->request('GET', $users[0]['@id'], [
            'headers' => [
                'Authorization' => "Bearer $token",
            ],
        ]);

        $this->assertSame(403, $userResponse->getStatusCode());
    }


    public function testGetCollectionIsAllowedForAdminUser()
    {
        $this->getUsers();
    }

    private function getUsers(): array
    {

        $client = self::createClient();

        $loginResponse = $client->request('POST', '/api/login', [
            'json' => [
                'email' => "user_admin@example.com",
                'password' => $this->parameterBag->get("app.alice.parameters.user_admin_pw"),
            ],
        ]);

        $this->assertSame(200, $loginResponse->getStatusCode());
        $token = $loginResponse->toArray()['token'];

        // Create a new site
        $userResponse = $client->request('GET', '/api/users', [
            'headers' => [
                'Authorization' => "Bearer $token",
            ],
        ]);

        $this->assertSame(200, $userResponse->getStatusCode());

        return $userResponse->toArray()['member'];
    }

}
