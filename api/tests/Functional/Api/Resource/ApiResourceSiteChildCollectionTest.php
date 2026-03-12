<?php

namespace App\Tests\Functional\Api\Resource;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Tests\Functional\Api\ApiTestProviderTrait;
use App\Tests\Functional\ApiTestRequestTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class ApiResourceSiteChildCollectionTest extends ApiTestCase
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

    public static function siteChildEndpointProvider(): array
    {
        return [
            'botany/charcoals' => ['/api/data/archaeological_sites/{siteId}/botany/charcoals', '/api/data/botany/charcoals'],
            'botany/seeds' => ['/api/data/archaeological_sites/{siteId}/botany/seeds', '/api/data/botany/seeds'],
            'individuals' => ['/api/data/archaeological_sites/{siteId}/individuals', '/api/data/individuals'],
            'microstratigraphic_units' => ['/api/data/archaeological_sites/{siteId}/microstratigraphic_units', '/api/data/microstratigraphic_units'],
            'potteries' => ['/api/data/archaeological_sites/{siteId}/potteries', '/api/data/potteries'],
            'zoo/bones' => ['/api/data/archaeological_sites/{siteId}/zoo/bones', '/api/data/zoo/bones'],
            'zoo/teeth' => ['/api/data/archaeological_sites/{siteId}/zoo/teeth', '/api/data/zoo/teeth'],
        ];
    }

    #[DataProvider('siteChildEndpointProvider')]
    public function testGetCollectionBySiteReturnsOnlyMembersOfSite(string $siteEndpoint, string $allEndpoint): void
    {
        $client = self::createClient();
        $token = $this->getUserToken($client, 'user_editor');

        $sitesResponse = $this->apiRequest($client, 'GET', '/api/data/archaeological_sites');
        $sites = $sitesResponse->toArray()['member'];
        $this->assertNotEmpty($sites, 'Fixture sites should exist');

        $site = $sites[0];
        $siteId = $site['id'];
        $siteIri = $site['@id'];

        $url = str_replace('{siteId}', (string) $siteId, $siteEndpoint);
        $siteResponse = $this->apiRequest($client, 'GET', $url, [
            'token' => $token,
        ]);

        $this->assertSame(200, $siteResponse->getStatusCode());
        $siteData = $siteResponse->toArray();
        $this->assertArrayHasKey('member', $siteData);

        foreach ($siteData['member'] as $member) {
            $suIri = is_array($member['stratigraphicUnit'])
                ? $member['stratigraphicUnit']['@id']
                : $member['stratigraphicUnit'];

            $suResponse = $this->apiRequest($client, 'GET', $suIri, [
                'token' => $token,
            ]);
            $su = $suResponse->toArray();

            $suSiteIri = is_array($su['site']) ? $su['site']['@id'] : $su['site'];
            $this->assertSame($siteIri, $suSiteIri, 'Resource must belong to the requested site');
        }
    }

    #[DataProvider('siteChildEndpointProvider')]
    public function testGetCollectionBySiteReturnsSubsetOfAll(string $siteEndpoint, string $allEndpoint): void
    {
        $client = self::createClient();
        $token = $this->getUserToken($client, 'user_editor');

        $sitesResponse = $this->apiRequest($client, 'GET', '/api/data/archaeological_sites');
        $sites = $sitesResponse->toArray()['member'];
        $this->assertNotEmpty($sites, 'Fixture sites should exist');

        $site = $sites[0];
        $siteId = $site['id'];

        $allResponse = $this->apiRequest($client, 'GET', $allEndpoint, [
            'token' => $token,
        ]);
        $allTotal = $allResponse->toArray()['totalItems'] ?? count($allResponse->toArray()['member']);

        $url = str_replace('{siteId}', (string) $siteId, $siteEndpoint);
        $siteResponse = $this->apiRequest($client, 'GET', $url, [
            'token' => $token,
        ]);

        $this->assertSame(200, $siteResponse->getStatusCode());
        $siteData = $siteResponse->toArray();
        $this->assertArrayHasKey('member', $siteData);
        $siteTotal = $siteData['totalItems'] ?? count($siteData['member']);

        $this->assertLessThanOrEqual($allTotal, $siteTotal, 'Site-scoped results should be a subset of all results');
    }
}
