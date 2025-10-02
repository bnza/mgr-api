<?php

namespace App\Tests\Functional\Api\Resource\Filter;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Symfony\Bundle\Test\Client;
use App\Tests\Functional\Api\ApiTestProviderTrait;
use App\Tests\Functional\ApiTestRequestTrait;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class GrantedAnalysisFilterTest extends ApiTestCase
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

        $responseAll = $this->apiRequest($client, 'GET', '/api/data/analyses');

        $this->assertSame(200, $responseAll->getStatusCode());

        $this->assertGreaterThan(0, $responseAll->toArray()['totalItems']);

        $responseWith = $this->apiRequest($client, 'GET', '/api/data/analyses', [
            'query' => ['granted' => 'true'],
        ]);

        $this->assertSame(200, $responseWith->getStatusCode());
        $this->assertEquals(0, $responseWith->toArray()['totalItems']);
    }

    public function testGrantedFilterWithAdminUserShouldReturnAllAnalyses(): void
    {
        $client = self::createClient();

        $countAll = $this->getTotalItemsCount($client, '/api/data/analyses');

        $token = $this->getUserToken($client, 'user_admin');

        $responseWith = $this->apiRequest($client, 'GET', '/api/data/analyses', [
            'query' => ['granted' => 'true'],
            'token' => $token,
        ]);

        $this->assertSame(200, $responseWith->getStatusCode());
        $countWith = $responseWith->toArray()['totalItems'];

        // Admin user should have access to all analyses
        $this->assertEquals($countAll, $countWith, 'Admin user should have access to all analyses');
    }

    public function testGrantedFilterWithFalseValueShouldReturnAllAnalyses(): void
    {
        $client = self::createClient();

        $countAll = $this->getTotalItemsCount($client, '/api/data/analyses');

        // Get token for authenticated user
        $token = $this->getUserToken($client, 'user_base');

        $response = $this->apiRequest($client, 'GET', '/api/data/analyses', [
            'query' => ['granted' => 'false'],
            'headers' => ['Authorization' => 'Bearer '.$token],
        ]);

        $this->assertSame(200, $response->getStatusCode());
        $countWith = $response->toArray()['totalItems'];

        // Should return all analyses when granted=false (filter disabled)
        $this->assertEquals($countAll, $countWith);
    }

    public function testGrantedFilterWithAuthenticatedUserShouldReturnOnlyOwnAnalyses(): void
    {
        $client = self::createClient();

        $token = $this->getUserToken($client, 'user_admin');

        $userData = [
            'email' => 'user_new@example.com',
            'plainPassword' => 'StrongPass123!',
            'roles' => ['ROLE_USER', 'ROLE_CERAMIC_SPECIALIST'],
        ];

        $createResponse = $this->apiRequest($client, 'POST', '/api/admin/users', [
            'token' => $token,
            'json' => $userData,
        ]);

        $this->assertSame(201, $createResponse->getStatusCode());
        $types = $this->apiRequest($client, 'GET', '/api/vocabulary/analysis/types')->toArray();
        $type = $types['member'][0]['@id'];

        // Get token for the new user
        $userToken = $this->getUserToken($client, $userData['email'], $userData['plainPassword']);

        // Create an analysis for the new user
        $analysisData = [
            'name' => 'Test Analysis',
            'description' => 'Test analysis for filter test',
            'identifier' => 'test.'.uniqid(),
            'year' => 2023,
            'type' => $type,
        ];

        $createAnalysisResponse = $this->apiRequest($client, 'POST', '/api/data/analyses', [
            'token' => $userToken,
            'json' => $analysisData,
        ]);

        $this->assertSame(201, $createAnalysisResponse->getStatusCode());

        $response = $this->apiRequest($client, 'GET', '/api/data/analyses', [
            'query' => ['granted' => 'true'],
            'token' => $userToken,
        ]);

        $this->assertSame(200, $response->getStatusCode());
        $count = $response->toArray()['totalItems'];

        // Should return only analyses created by this user
        $this->assertEquals(1, $count);

        $grantedAnalyses = $response->toArray()['member'];
        $this->assertCount(1, $grantedAnalyses);
        $this->assertEquals($createAnalysisResponse->toArray()['@id'], $grantedAnalyses[0]['@id']);
    }

    public function testGrantedFilterWithUserWithoutOwnAnalysesShouldReturnEmptySet(): void
    {
        $client = self::createClient();

        $token = $this->getUserToken($client, 'user_admin');

        $userData = [
            'email' => 'user_without_analyses@example.com',
            'plainPassword' => 'StrongPass123!',
            'roles' => ['ROLE_USER'],
        ];

        $createResponse = $this->apiRequest($client, 'POST', '/api/admin/users', [
            'token' => $token,
            'json' => $userData,
        ]);

        $this->assertSame(201, $createResponse->getStatusCode());

        // Get token for the user without any analyses
        $userToken = $this->getUserToken($client, $userData['email'], $userData['plainPassword']);

        $response = $this->apiRequest($client, 'GET', '/api/data/analyses', [
            'query' => ['granted' => 'true'],
            'headers' => ['Authorization' => 'Bearer '.$userToken],
        ]);

        $this->assertSame(200, $response->getStatusCode());
        $count = $response->toArray()['totalItems'];

        // Should return empty set as user has no analyses
        $this->assertEquals(0, $count);
    }
}
