<?php

namespace App\Tests\Functional\Api\Resource\Filter;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Symfony\Bundle\Test\Client;
use App\Tests\Functional\Api\ApiTestProviderTrait;
use App\Tests\Functional\ApiTestRequestTrait;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class GrantedContextFilterTest extends ApiTestCase
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

        // Get token for a user that doesn't have site privileges
        $token = $this->getUserToken($client, $userData['email'], $userData['plainPassword']);

        $response = $this->apiRequest($client, 'GET', '/api/data/contexts', [
            'query' => ['granted' => 'true'],
            'headers' => ['Authorization' => 'Bearer '.$token],
        ]);

        $this->assertSame(200, $response->getStatusCode());
        $count = $response->toArray()['totalItems'];

        // Should return empty set since user has no site privileges
        $this->assertEquals(0, $count);
    }

    public function testGrantedFilterWithAuthenticatedUserWithPrivilegesShouldReturnTheGrantedContexts(): void
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

        $sites = $this->getArchaeologicalSites();

        // Grant privileges to first two sites
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

        // Get all contexts from the sites the user has access to
        $allContextsResponse = $this->apiRequest($client, 'GET', '/api/data/contexts?itemsPerPage=1000', [
            'token' => $this->getUserToken($client, 'user_admin'),
        ]);

        $allContexts = $allContextsResponse->toArray()['member'];

        // Filter to only those from sites with privileges
        $expectedContexts = array_filter($allContexts, function ($context) use ($sites) {
            return $context['site']['@id'] === $sites[0]['@id'] || $context['site']['@id'] === $sites[1]['@id'];
        });

        // Get token for the user with privileges
        $userToken = $this->getUserToken($client, $userData['email'], $userData['plainPassword']);

        $response = $this->apiRequest($client, 'GET', '/api/data/contexts', [
            'query' => ['granted' => 'true'],
            'headers' => ['Authorization' => 'Bearer '.$userToken],
        ]);

        $this->assertSame(200, $response->getStatusCode());
        $responseData = $response->toArray();
        $count = $responseData['totalItems'];
        $grantedContexts = $responseData['member'];

        // Should return only contexts from sites where user has privileges
        $this->assertEquals(count($expectedContexts), $count);
        $this->assertCount(count($expectedContexts), $grantedContexts);

        // Verify all returned contexts are from sites with privileges
        foreach ($grantedContexts as $context) {
            $this->assertTrue(
                $context['site']['@id'] === $sites[0]['@id'] || $context['site']['@id'] === $sites[1]['@id'],
                'Context should only be from sites where user has privileges'
            );
        }
    }
}
