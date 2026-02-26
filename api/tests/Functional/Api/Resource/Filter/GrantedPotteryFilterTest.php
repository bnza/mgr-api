<?php

namespace App\Tests\Functional\Api\Resource\Filter;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Symfony\Bundle\Test\Client;
use App\Tests\Functional\Api\ApiTestProviderTrait;
use App\Tests\Functional\ApiTestRequestTrait;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class GrantedPotteryFilterTest extends ApiTestCase
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

        $response = $this->apiRequest($client, 'GET', '/api/data/potteries', [
            'token' => $token,
            'query' => ['granted' => 'true'],
        ]);

        $this->assertSame(200, $response->getStatusCode());
        $count = $response->toArray()['totalItems'];

        // Should return empty set since user has no site privileges
        $this->assertEquals(0, $count);
    }

    public function testGrantedFilterWithAuthenticatedUserWithPrivilegesShouldReturnTheGrantedPotteries(): void
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

        $sites = array_filter($this->getArchaeologicalSites(), function ($site) {
            return 'ME' === $site['code'];
        });

        $this->assertCount(1, $sites);
        $siteIri = array_pop($sites)['@id'];

        // Grant privileges to first two sites
        $privilegeData = [
            'user' => $userId,
            'site' => $siteIri,
            'privilege' => 0,
        ];

        $createPrivilegeResponse = $this->apiRequest($client, 'POST', '/api/admin/site_user_privileges', [
            'token' => $token,
            'json' => $privilegeData,
        ]);

        $this->assertSame(201, $createPrivilegeResponse->getStatusCode());

        // Get all potteries from the sites the user has access to
        $allPotteriesResponse = $this->apiRequest($client, 'GET', '/api/data/potteries?itemsPerPage=1000', [
            'token' => $token,
        ]);

        $allPotteries = $allPotteriesResponse->toArray()['member'];

        // Filter to only those from sites with privileges
        $expectedPotteries = array_filter($allPotteries, function ($pottery) use ($siteIri) {
            return $pottery['stratigraphicUnit']['site']['@id'] === $siteIri;
        });

        // Get token for the user with privileges
        $userToken = $this->getUserToken($client, $userData['email'], $userData['plainPassword']);

        $response = $this->apiRequest($client, 'GET', '/api/data/potteries', [
            'query' => ['granted' => 'true'],
            'headers' => ['Authorization' => 'Bearer '.$userToken],
        ]);

        $this->assertSame(200, $response->getStatusCode());
        $responseData = $response->toArray();
        $count = $responseData['totalItems'];
        $grantedPotteries = $responseData['member'];

        // Should return only potteries from sites where user has privileges
        $this->assertGreaterThan(0, count($expectedPotteries), 'Expected at least one pottery to be returned');
        $this->assertCount(count($expectedPotteries), $grantedPotteries);

        // Verify all returned potteries are from sites with privileges
        foreach ($grantedPotteries as $pottery) {
            $this->assertTrue(
                $pottery['stratigraphicUnit']['site']['@id'] === $siteIri,
                'Pottery should only be from sites where user has privileges'
            );
        }
    }

    public function testGrantedFilterWithoutQueryParameterShouldNotApplyFilter(): void
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

        // Get token for the new user (without privileges)
        $userToken = $this->getUserToken($client, $userData['email'], $userData['plainPassword']);

        // Request without granted parameter should return all accessible potteries (if any)
        $responseWithoutGranted = $this->apiRequest($client, 'GET', '/api/data/potteries', [
            'token' => $userToken,
        ]);

        // Request with granted=false should also not apply the granted filter
        $responseWithGrantedFalse = $this->apiRequest($client, 'GET', '/api/data/potteries', [
            'query' => ['granted' => 'false'],
            'token' => $userToken,
        ]);

        // Request with granted=true should apply the granted filter
        $responseWithGrantedTrue = $this->apiRequest($client, 'GET', '/api/data/potteries', [
            'query' => ['granted' => 'true'],
            'token' => $userToken,
        ]);

        $this->assertSame(200, $responseWithoutGranted->getStatusCode());
        $this->assertSame(200, $responseWithGrantedFalse->getStatusCode());
        $this->assertSame(200, $responseWithGrantedTrue->getStatusCode());

        $countWithoutGranted = $responseWithoutGranted->toArray()['totalItems'];
        $countWithGrantedFalse = $responseWithGrantedFalse->toArray()['totalItems'];
        $countWithGrantedTrue = $responseWithGrantedTrue->toArray()['totalItems'];

        // Without granted filter, the response depends on other access controls
        // With granted=true and no privileges, should return 0
        $this->assertEquals(0, $countWithGrantedTrue);

        // Without granted parameter and granted=false should behave the same
        $this->assertEquals($countWithoutGranted, $countWithGrantedFalse);
    }

    public function testGrantedFilterWithUserAdminShouldReturnAllSet(): void
    {
        $client = self::createClient();

        $token = $this->getUserToken($client, 'user_admin');

        // Get all potteries without any filter to establish baseline
        $allPotteriesResponse = $this->apiRequest($client, 'GET', '/api/data/potteries?itemsPerPage=1000', [
            'token' => $token,
        ]);

        $this->assertSame(200, $allPotteriesResponse->getStatusCode());
        $allPotteriesCount = $allPotteriesResponse->toArray()['totalItems'];

        // Get potteries with granted=true filter using user_admin
        $grantedPotteriesResponse = $this->apiRequest($client, 'GET', '/api/data/potteries', [
            'query' => ['granted' => 'true'],
            'token' => $token,
        ]);

        $this->assertSame(200, $grantedPotteriesResponse->getStatusCode());
        $grantedPotteriesCount = $grantedPotteriesResponse->toArray()['totalItems'];

        // Admin user should see all potteries when using granted filter
        $this->assertEquals($allPotteriesCount, $grantedPotteriesCount, 'Admin user with granted filter should return all pottery items');
        $this->assertGreaterThan(0, $grantedPotteriesCount, 'Expected at least one pottery to be returned for admin user');
    }
}
