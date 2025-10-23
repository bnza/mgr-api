<?php

namespace App\Tests\Functional\Api\Resource\Filter;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Symfony\Bundle\Test\Client;
use App\Tests\Functional\Api\ApiTestProviderTrait;
use App\Tests\Functional\ApiTestRequestTrait;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class SearchSedimentCoreFilterTest extends ApiTestCase
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

    public function testSearchFilterWithOneChunkString(): void
    {
        $client = self::createClient();

        $response = $this->apiRequest($client, 'GET', '/api/data/sediment_cores', [
            'query' => ['search' => 'SE'],
        ]);

        $this->assertResponseIsSuccessful();
        $data = $response->toArray();
        $this->assertArrayHasKey('member', $data);

        // Verify that results contain sediment cores from sites with codes ending in 'SE'
        foreach ($data['member'] as $item) {
            $this->assertStringEndsWith('SE', strtoupper($item['site']['code']));
        }
    }

    public function testSearchFilterWithOneChunkNumeric(): void
    {
        $client = self::createClient();

        $response = $this->apiRequest($client, 'GET', '/api/data/sediment_cores', [
            'query' => ['search' => '5'],
        ]);

        $this->assertResponseIsSuccessful();
        $data = $response->toArray();
        $this->assertArrayHasKey('member', $data);

        // Verify that results contain sediment cores with numbers ending in '5'
        foreach ($data['member'] as $item) {
            $this->assertStringEndsWith('5', (string) $item['number']);
        }
    }

    public function testSearchFilterCanBeCombinedWithUnaccentedFilter(): void
    {
        $client = self::createClient();

        $response = $this->apiRequest($client, 'GET', '/api/data/sediment_cores', [
            'query' => ['search' => '5', 'description' => 'foundation'],
        ]);

        $this->assertResponseIsSuccessful();
        $data = $response->toArray();
        $this->assertArrayHasKey('member', $data);

        // Verify that results contain sediment cores with numbers ending in '5' and matching description
        foreach ($data['member'] as $item) {
            $this->assertStringEndsWith('5', (string) $item['number']);
            if (isset($item['description']) && null !== $item['description']) {
                $this->assertStringContainsStringIgnoringCase('foundation', (string) $item['description']);
            }
        }
    }

    public function testSearchFilterWithTwoChunksStringAndNumeric(): void
    {
        $client = self::createClient();

        $response = $this->apiRequest($client, 'GET', '/api/data/sediment_cores', [
            'query' => ['search' => 'SC 25'],
        ]);

        $this->assertResponseIsSuccessful();
        $data = $response->toArray();
        $this->assertArrayHasKey('member', $data);

        $this->assertGreaterThan(0, $data['totalItems']);

        // Verify that results match both site code and number criteria
        foreach ($data['member'] as $item) {
            $this->assertStringStartsWith('SC', strtoupper($item['site']['code']));
            $this->assertTrue(str_ends_with($item['number'], '25') || str_ends_with($item['year'], '25'));
        }
    }

    public function testSearchFilterWithTwoChunksNumericAndNumeric(): void
    {
        $client = self::createClient();

        $response = $this->apiRequest($client, 'GET', '/api/data/sediment_cores', [
            'query' => ['search' => '2025 5'],
        ]);

        $this->assertResponseIsSuccessful();
        $data = $response->toArray();
        $this->assertArrayHasKey('member', $data);

        // Verify that results match both number and year criteria
        foreach ($data['member'] as $item) {
            $this->assertStringEndsWith('5', (string) $item['number']);
            $this->assertStringEndsWith('2025', (string) $item['year']);
        }
    }

    public function testSearchFilterWithInvalidCombinationReturnsEmptySet(): void
    {
        $client = self::createClient();

        // Test invalid two chunk combination (string + string)
        $response = $this->apiRequest($client, 'GET', '/api/data/sediment_cores', [
            'query' => ['search' => 'ABC DEF'],
        ]);

        $this->assertResponseIsSuccessful();
        $data = $response->toArray();
        $this->assertArrayHasKey('member', $data);
        $this->assertEmpty($data['member'], 'Invalid combination should return empty results');
    }

    public function testSearchFilterWithVariousDelimiters(): void
    {
        $client = self::createClient();

        // Test with dot delimiter
        $response1 = $this->apiRequest($client, 'GET', '/api/data/sediment_cores', [
            'query' => ['search' => 'SE.5'],
        ]);

        // Test with space delimiter
        $response2 = $this->apiRequest($client, 'GET', '/api/data/sediment_cores', [
            'query' => ['search' => 'SE 5'],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertResponseIsSuccessful();

        $data1 = $response1->toArray();
        $data2 = $response2->toArray();

        // Both should return the same results since they use the same chunks
        $this->assertEquals($data1['member'], $data2['member']);
    }

    public function testSearchFilterWithEmptyValueReturnsAllResults(): void
    {
        $client = self::createClient();

        $response = $this->apiRequest($client, 'GET', '/api/data/sediment_cores', [
            'query' => ['search' => ''],
        ]);

        $this->assertResponseIsSuccessful();
        $data = $response->toArray();
        $this->assertArrayHasKey('member', $data);

        // Empty search should not filter results (same as no search parameter)
        $responseNoSearch = $this->apiRequest($client, 'GET', '/api/data/sediment_cores');

        $dataNoSearch = $responseNoSearch->toArray();
        $this->assertEquals($dataNoSearch['member'], $data['member']);
    }

    public function testSearchFilterParameterIsOptional(): void
    {
        $client = self::createClient();

        // Request without search parameter should work
        $response = $this->apiRequest($client, 'GET', '/api/data/sediment_cores');

        $this->assertResponseIsSuccessful();
        $data = $response->toArray();
        $this->assertArrayHasKey('member', $data);
    }
}
