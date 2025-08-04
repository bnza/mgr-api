<?php

namespace App\Tests\Functional\Api\Resource;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Tests\Functional\ApiTestRequestTrait;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class ApiSubresourceSiteUserPrivilegeSiteTest extends ApiTestCase
{
    use ApiTestRequestTrait;

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

    public function testGetSiteUserPrivilegesSiteSubresourceIsDeniedForAnonymousUser(): void
    {
        $client = self::createClient();

        $sites = $this->getSites();

        $siteId = $sites[0]['id'];

        $response = $this->apiRequest($client, 'GET', "/api/admin/sites/{$siteId}/site_user_privileges");

        $this->assertSame(401, $response->getStatusCode());
    }

    public function testGetSiteUserPrivilegesSiteSubresourceSucceedForExistingSite(): void
    {
        $client = self::createClient();

        $token = $this->getUserToken($client, 'user_admin');

        // First get the total count of all user privileges
        $allPrivilegesResponse = $this->apiRequest($client, 'GET', '/api/admin/site_user_privileges', [
            'token' => $token,
        ]);

        $this->assertSame(200, $allPrivilegesResponse->getStatusCode());
        $allPrivilegesData = $allPrivilegesResponse->toArray();
        $totalPrivileges = $allPrivilegesData['totalItems'];

        // Get available sites to pick one for testing
        $sites = $this->getSites();
        $this->assertNotEmpty($sites, 'No sites available for testing');
        $siteId = $sites[0]['id'];

        // Test the subresource endpoint
        $response = $this->apiRequest($client, 'GET', "/api/admin/sites/{$siteId}/site_user_privileges", [
            'token' => $token,
        ]);

        $this->assertSame(200, $response->getStatusCode());

        $responseData = $response->toArray();
        $this->assertArrayHasKey('@context', $responseData);
        $this->assertArrayHasKey('@type', $responseData);
        $this->assertArrayHasKey('member', $responseData);
        $this->assertArrayHasKey('totalItems', $responseData);
        $this->assertIsArray($responseData['member']);

        $subresourceTotal = $responseData['totalItems'];

        // Verify that the subresource has fewer or equal items than the main resource
        $this->assertLessThanOrEqual($totalPrivileges, $subresourceTotal);

        // Verify that all returned privileges belong to the specified site
        foreach ($responseData['member'] as $privilege) {
            $this->assertArrayHasKey('site', $privilege);
            $this->assertSame($siteId, $privilege['site']['id']);
        }

        // Additional check: the subresource should have fewer items than total unless there's only one site
        if (count($sites) > 1) {
            $this->assertLessThan(
                $totalPrivileges,
                $subresourceTotal,
                'Subresource should contain fewer privileges than the total when multiple sites exist'
            );
        }
    }

    public function testGetSiteUserPrivilegesSubresourceReturnsAnEmptySetForNonExistentSite(): void
    {
        $client = self::createClient();

        $token = $this->getUserToken($client, 'user_admin');

        // Use a non-existent site ID (integer, not UUID)
        $nonExistentSiteId = 99999;

        // Test the subresource endpoint with non-existent site
        $response = $this->apiRequest($client, 'GET', "/api/admin/sites/{$nonExistentSiteId}/site_user_privileges", [
            'token' => $token,
        ]);

        $this->assertSame(200, $response->getStatusCode());

        $responseData = $response->toArray();
        $this->assertArrayHasKey('@context', $responseData);
        $this->assertArrayHasKey('@type', $responseData);
        $this->assertArrayHasKey('member', $responseData);
        $this->assertArrayHasKey('totalItems', $responseData);
        $this->assertIsArray($responseData['member']);

        $this->assertSame(0, $responseData['totalItems']);
    }
}
