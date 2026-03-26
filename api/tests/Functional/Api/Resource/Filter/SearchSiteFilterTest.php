<?php

namespace App\Tests\Functional\Api\Resource\Filter;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Symfony\Bundle\Test\Client;
use App\Tests\Functional\Api\ApiTestProviderTrait;
use App\Tests\Functional\ApiTestRequestTrait;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class SearchSiteFilterTest extends ApiTestCase
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

    public function testSearchFilterWithExistingCode(): void
    {
        $client = self::createClient();

        $response = $this->apiRequest($client, 'GET', '/api/data/archaeological_sites', [
            'query' => ['search' => 'ME'],
        ]);

        $this->assertResponseIsSuccessful();
        $data = $response->toArray();
        $this->assertArrayHasKey('member', $data);

        // Verify that results contain contexts with names containing 'fill'
        foreach ($data['member'] as $item) {
            $this->assertEquals('ME', $item['code']);
        }
    }

    public function testSearchFilterCanBeCombinedWithUnaccentedFilter(): void
    {
        $client = self::createClient();

        $response = $this->apiRequest($client, 'GET', '/api/data/archaeological_sites', [
            'query' => ['search' => 'ME', 'name' => 'nathan'],
        ]);

        $this->assertResponseIsSuccessful();
        $data = $response->toArray();
        $this->assertArrayHasKey('member', $data);

        // Verify that results match both site code ending with 'ME' AND name containing 'fill'
        foreach ($data['member'] as $item) {
            $this->assertEquals('ME', $item['code']);
        }
    }

    public function testSearchFilterSamplingSite(): void
    {
        $client = self::createClient();

        // Search by code (starts with)
        $response = $this->apiRequest($client, 'GET', '/api/data/sampling_sites', [
            'query' => ['search' => 'SC1'],
        ]);
        $this->assertResponseIsSuccessful();
        $data = $response->toArray();
        $this->assertCount(1, $data['member']);
        $this->assertEquals('SC1', $data['member'][0]['code']);

        // Search by name (contains) - name is 'Sediment cores 1'
        $response = $this->apiRequest($client, 'GET', '/api/data/sampling_sites', [
            'query' => ['search' => 'cores'],
        ]);
        $this->assertResponseIsSuccessful();
        $data = $response->toArray();
        $this->assertGreaterThanOrEqual(3, count($data['member'])); // All 3 sediment cores sites
        foreach ($data['member'] as $item) {
            $this->assertStringContainsString('Sediment cores', $item['name']);
        }
    }

    public function testSearchFilterPaleoclimateSamplingSite(): void
    {
        $client = self::createClient();

        // Search by code (starts with)
        $response = $this->apiRequest($client, 'GET', '/api/data/paleoclimate_sampling_sites', [
            'query' => ['search' => 'PCS1'],
        ]);
        $this->assertResponseIsSuccessful();
        $data = $response->toArray();
        // Since it's PCS1, it might match PCS10 too if it's "starts with" PCS1
        foreach ($data['member'] as $item) {
            $this->assertStringStartsWith('PCS1', $item['code']);
        }

        // Search by name (contains) - 'castanar' is in 'Cueva de Castañar'
        $response = $this->apiRequest($client, 'GET', '/api/data/paleoclimate_sampling_sites', [
            'query' => ['search' => 'castanar'],
        ]);
        $this->assertResponseIsSuccessful();
        $data = $response->toArray();
        $this->assertCount(1, $data['member']);
        $this->assertEquals('Cueva de Castañar', $data['member'][0]['name']);
    }
}
