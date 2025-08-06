<?php

namespace App\Tests\Functional\Api\Resource\Filter;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Symfony\Bundle\Test\Client;
use App\Tests\Functional\Api\ApiTestProviderTrait;
use App\Tests\Functional\ApiTestRequestTrait;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class GrantedSiteFilterTest extends ApiTestCase
{
    use ApiTestRequestTrait;
    use ApiTestProviderTrait;

    private Client $client;
    private ?ParameterBagInterface $parameterBag = null;

    protected function setUp(): void
    {
        parent::setUp();
        static::$alwaysBootKernel = false;
        $this->parameterBag = self::getContainer()->get(ParameterBagInterface::class);
        $this->client = static::createClient();
    }

    protected function tearDown(): void
    {
        $this->parameterBag = null;
        parent::tearDown();
    }

    public function testGrantedFilterWithUnauthenticatedUserShouldReturnEmptySet(): void
    {
        $client = self::createClient();

        $responseAll = $this->apiRequest($client, 'GET', '/api/data/sites');

        $this->assertSame(200, $responseAll->getStatusCode());

        $this->assertGreaterThan(0, $responseAll->toArray()['totalItems']);

        $responseWith = $this->apiRequest($client, 'GET', '/api/data/sites', [
            'query' => ['granted' => 'true'],
        ]);

        $this->assertSame(200, $responseWith->getStatusCode());
        $this->assertEquals(0, $responseWith->toArray()['totalItems']);
    }

    public function testGrantedFilterWithAdminUserShouldReturnAllSites(): void
    {
        $client = self::createClient();

        $countAll = $this->getTotalItemsCount($client, '/api/data/sites');

        $token = $this->getUserToken($client, 'user_admin');

        $responseWith = $this->apiRequest($client, 'GET', '/api/data/sites', [
            'query' => ['granted' => 'true'],
            'token' => $token,
        ]);

        $this->assertSame(200, $responseWith->getStatusCode());
        $countWith = $responseWith->toArray()['totalItems'];

        // Should return sites where the user has privileges
        // The exact count depends on fixtures, but should not be empty for admin user
        $this->assertEquals($countAll, $countWith, 'Admin user should have access to all sites');
    }

    public function testGrantedFilterWithFalseValueShouldReturnAllSites(): void
    {
        $client = self::createClient();

        $countAll = $this->getTotalItemsCount($client, '/api/data/sites');

        // Get token for authenticated user
        $token = $this->getUserToken($client, 'user_base');

        $response = $this->apiRequest($client, 'GET', '/api/data/sites', [
            'query' => ['granted' => 'false'],
            'headers' => ['Authorization' => 'Bearer ' . $token],
        ]);

        $this->assertSame(200, $response->getStatusCode());
        $countWith = $response->toArray()['totalItems'];

        // Should return all sites when granted=false (filter disabled)
        $this->assertEquals($countAll, $countWith);
    }

    public function testGrantedFilterWithAuthenticatedUserWithoutPrivilegesShouldReturnEmptySet(): void
    {
        $client = self::createClient();

        $token = $this->getUserToken($client, 'user_admin');

        $userData = [
            'email' => 'user_new@example.com',
            'plainPassword' => 'StrongPass123!',
            'roles' => ['ROLE_USER'],
        ];

        $createResponse = $this->apiRequest($client, 'POST', '/api/admin/users', [
            'token' => $token,
            'json' => $userData,
        ]);

        $this->assertSame(201, $createResponse->getStatusCode());

        // Get token for a user that might not have site privileges
        $token = $this->getUserToken($client, $userData['email'], $userData['plainPassword']);

        $response = $this->apiRequest($client, 'GET', '/api/data/sites', [
            'query' => ['granted' => 'true'],
            'headers' => ['Authorization' => 'Bearer ' . $token],
        ]);

        $this->assertSame(200, $response->getStatusCode());
        $count = $response->toArray()['totalItems'];

        // Should return empty set or only sites where user has privileges
        $this->assertEquals(0, $count);
    }

    public function testGrantedFilterWithAuthenticatedUserWithPrivilegesShouldReturnTheGrantedSites(): void
    {
        $client = self::createClient();

        $token = $this->getUserToken($client, 'user_admin');

        $userData = [
            'email' => 'user_new@example.com',
            'plainPassword' => 'StrongPass123!',
            'roles' => ['ROLE_USER'],
        ];

        $createUserResponse = $this->apiRequest($client, 'POST', '/api/admin/users', [
            'token' => $token,
            'json' => $userData,
        ]);

        $this->assertSame(201, $createUserResponse->getStatusCode());
        $userId = $createUserResponse->toArray()['@id'];

        $sites = $this->getSites();

        $privilegeData = [
            'user' => $userId,
            'site' => $sites[0]['@id'],
            'privilege' => 0,
        ];

        $createPrivilegeResponse = $this->apiRequest($client, 'POST', '/api/admin/site_user_privileges', [
            'token' => $token,
            'json' => $privilegeData,
        ]);

        $this->assertSame(201, $createPrivilegeResponse->getStatusCode());

        $privilegeData = [
            'user' => $userId,
            'site' => $sites[1]['@id'],
            'privilege' => 1,
        ];

        $createPrivilegeResponse = $this->apiRequest($client, 'POST', '/api/admin/site_user_privileges', [
            'token' => $token,
            'json' => $privilegeData,
        ]);

        $this->assertSame(201, $createPrivilegeResponse->getStatusCode());

        // Get token for a user that might not have site privileges
        $token = $this->getUserToken($client, $userData['email'], $userData['plainPassword']);

        $response = $this->apiRequest($client, 'GET', '/api/data/sites', [
            'query' => ['granted' => 'true'],
            'headers' => ['Authorization' => 'Bearer ' . $token],
        ]);

        $this->assertSame(200, $response->getStatusCode());
        $count = $response->toArray()['totalItems'];
        $this->assertEquals(2, $count);

        $grantedSites = $response->toArray()['member'];
        $this->assertCount(2, $grantedSites);
        $this->assertEquals($sites[0]['@id'], $grantedSites[0]['@id']);
        $this->assertEquals($sites[1]['@id'], $grantedSites[1]['@id']);
    }
}
