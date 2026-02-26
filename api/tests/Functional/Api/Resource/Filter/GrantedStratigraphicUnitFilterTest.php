<?php

namespace App\Tests\Functional\Api\Resource\Filter;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Symfony\Bundle\Test\Client;
use App\Tests\Functional\Api\ApiTestProviderTrait;
use App\Tests\Functional\ApiTestRequestTrait;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class GrantedStratigraphicUnitFilterTest extends ApiTestCase
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

        $response = $this->apiRequest($client, 'GET', '/api/data/stratigraphic_units', [
            'query' => ['granted' => 'true'],
            'headers' => ['Authorization' => 'Bearer '.$token],
        ]);

        $this->assertSame(200, $response->getStatusCode());
        $count = $response->toArray()['totalItems'];

        // Should return empty set since user has no site privileges
        $this->assertEquals(0, $count);
    }

    public function testGrantedFilterWithAuthenticatedUserWithPrivilegesShouldReturnTheGrantedStratigraphicUnits(): void
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

        // Get all stratigraphic units from the sites the user has access to
        $allStratigraphicUnitsResponse = $this->apiRequest($client, 'GET', '/api/data/stratigraphic_units?itemsPerPage=1000', [
            'token' => $this->getUserToken($client, 'user_admin'),
        ]);

        $allStratigraphicUnits = $allStratigraphicUnitsResponse->toArray()['member'];

        // Filter to only those from sites with privileges
        $expectedStratigraphicUnits = array_filter($allStratigraphicUnits, function ($su) use ($sites) {
            return $su['site']['@id'] === $sites[0]['@id'] || $su['site']['@id'] === $sites[1]['@id'];
        });

        // Get token for the user with privileges
        $userToken = $this->getUserToken($client, $userData['email'], $userData['plainPassword']);

        $response = $this->apiRequest($client, 'GET', '/api/data/stratigraphic_units', [
            'query' => ['granted' => 'true'],
            'headers' => ['Authorization' => 'Bearer '.$userToken],
        ]);

        $this->assertSame(200, $response->getStatusCode());
        $responseData = $response->toArray();
        $count = $responseData['totalItems'];
        $grantedStratigraphicUnits = $responseData['member'];

        // Should return only stratigraphic units from sites where user has privileges
        $this->assertEquals(count($expectedStratigraphicUnits), $count);
        $this->assertCount(count($expectedStratigraphicUnits), $grantedStratigraphicUnits);

        // Verify all returned stratigraphic units are from sites with privileges
        foreach ($grantedStratigraphicUnits as $su) {
            $this->assertTrue(
                $su['site']['@id'] === $sites[0]['@id'] || $su['site']['@id'] === $sites[1]['@id'],
                'Stratigraphic unit should only be from sites where user has privileges'
            );
        }
    }
}
