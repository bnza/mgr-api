<?php

namespace App\Tests\Functional\Api\Resource;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Tests\Functional\Api\ApiTestProviderTrait;
use App\Tests\Functional\ApiTestRequestTrait;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class ApiResourceBotanyCharcoalTest extends ApiTestCase
{
    use ApiTestRequestTrait;
    use ApiTestProviderTrait;

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

    public function testGetCollectionBySiteReturnsCharcoalsForGivenSite(): void
    {
        $client = self::createClient();
        $token = $this->getUserToken($client, 'user_editor');

        // Get a site that has charcoals
        $sitesResponse = $this->apiRequest($client, 'GET', '/api/data/archaeological_sites');
        $sites = $sitesResponse->toArray()['member'];
        $this->assertNotEmpty($sites, 'Fixture sites should exist');

        $site = $sites[0];
        $siteId = $site['id'];
        $siteIri = $site['@id'];

        // Fetch charcoals via the new site-scoped endpoint
        $response = $this->apiRequest($client, 'GET', "/api/data/archaeological_sites/{$siteId}/botany/charcoals", [
            'token' => $token,
        ]);

        $this->assertSame(200, $response->getStatusCode());
        $data = $response->toArray();
        $this->assertArrayHasKey('member', $data);

        // Verify all returned charcoals belong to the given site
        foreach ($data['member'] as $charcoal) {
            $suIri = is_array($charcoal['stratigraphicUnit'])
                ? $charcoal['stratigraphicUnit']['@id']
                : $charcoal['stratigraphicUnit'];

            $suResponse = $this->apiRequest($client, 'GET', $suIri, [
                'token' => $token,
            ]);
            $su = $suResponse->toArray();
            $suSiteIri = is_array($su['site']) ? $su['site']['@id'] : $su['site'];

            $this->assertSame($siteIri, $suSiteIri, 'Charcoal must belong to the requested site');
        }
    }

    public function testGetCollectionBySiteReturnsSubsetOfAllCharcoals(): void
    {
        $client = self::createClient();
        $token = $this->getUserToken($client, 'user_editor');

        // Get all charcoals
        $allResponse = $this->apiRequest($client, 'GET', '/api/data/botany/charcoals', [
            'token' => $token,
        ]);
        $allData = $allResponse->toArray();
        $allTotal = $allData['totalItems'] ?? count($allData['member']);

        // Get a site
        $sitesResponse = $this->apiRequest($client, 'GET', '/api/data/archaeological_sites');
        $sites = $sitesResponse->toArray()['member'];
        $this->assertNotEmpty($sites);

        $site = $sites[0];
        $siteId = $site['id'];

        // Get charcoals for that site
        $siteResponse = $this->apiRequest($client, 'GET', "/api/data/archaeological_sites/{$siteId}/botany/charcoals", [
            'token' => $token,
        ]);
        $siteData = $siteResponse->toArray();
        $siteTotal = $siteData['totalItems'] ?? count($siteData['member']);

        $this->assertLessThanOrEqual($allTotal, $siteTotal, 'Site-scoped charcoals should be a subset of all charcoals');
    }
}
