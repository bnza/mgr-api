<?php

namespace App\Tests\Functional\Api\Resource\Filter;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Symfony\Bundle\Test\Client;
use App\Tests\Functional\Api\ApiTestProviderTrait;
use App\Tests\Functional\ApiTestRequestTrait;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class SearchContextFilterTest extends ApiTestCase
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

        $response = $this->apiRequest($client, 'GET', '/api/data/contexts', [
            'query' => ['search' => 'fill'],
        ]);

        $this->assertResponseIsSuccessful();
        $data = $response->toArray();
        $this->assertArrayHasKey('member', $data);

        // Verify that results contain contexts with names containing 'fill'
        foreach ($data['member'] as $item) {
            $this->assertStringContainsStringIgnoringCase('fill', $item['name']);
        }
    }

    public function testSearchFilterWithTwoChunksSiteCodeAndName(): void
    {
        $client = self::createClient();

        $response = $this->apiRequest($client, 'GET', '/api/data/contexts', [
            'query' => ['search' => 'ME.fill'],
        ]);

        $this->assertResponseIsSuccessful();
        $data = $response->toArray();
        $this->assertArrayHasKey('member', $data);

        // Verify that results match both site code ending with 'ME' AND name containing 'fill'
        foreach ($data['member'] as $item) {
            $this->assertStringEndsWith('ME', strtoupper($item['site']['code']));
            $this->assertStringContainsStringIgnoringCase('fill', $item['name']);
        }
    }

    public function testSearchFilterWithTwoChunksPartialSiteCodeAndName(): void
    {
        $client = self::createClient();

        $response = $this->apiRequest($client, 'GET', '/api/data/contexts', [
            'query' => ['search' => 'A.fill'],
        ]);

        $this->assertResponseIsSuccessful();
        $data = $response->toArray();
        $this->assertArrayHasKey('member', $data);

        // Verify that results match both site code ending with 'A' AND name containing 'fill'
        foreach ($data['member'] as $item) {
            $this->assertStringEndsWith('A', strtoupper($item['site']['code']));
            $this->assertStringContainsStringIgnoringCase('fill', $item['name']);
        }
    }

    public function testSearchFilterWithTwoChunksNumericInName(): void
    {
        $client = self::createClient();

        $response = $this->apiRequest($client, 'GET', '/api/data/contexts', [
            'query' => ['search' => 'ME.1'],
        ]);

        $this->assertResponseIsSuccessful();
        $data = $response->toArray();
        $this->assertArrayHasKey('member', $data);

        // Verify that results match both site code ending with 'ME' AND name containing '1'
        foreach ($data['member'] as $item) {
            $this->assertStringEndsWith('ME', strtoupper($item['site']['code']));
            $this->assertStringContainsString('1', $item['name']);
        }
    }

    public function testSearchFilterWithInvalidCombinationReturnsEmptySet(): void
    {
        $client = self::createClient();

        // Test combination that should not match any fixtures
        $response = $this->apiRequest($client, 'GET', '/api/data/contexts', [
            'query' => ['search' => 'NONEXISTENT.impossible'],
        ]);

        $this->assertResponseIsSuccessful();
        $data = $response->toArray();
        $this->assertArrayHasKey('member', $data);
        $this->assertEmpty($data['member'], 'Non-matching combination should return empty results');
    }

    public function testSearchFilterWithCaseInsensitivity(): void
    {
        $client = self::createClient();

        // Test uppercase search term
        $response1 = $this->apiRequest($client, 'GET', '/api/data/contexts', [
            'query' => ['search' => 'FILL'],
        ]);

        // Test lowercase search term
        $response2 = $this->apiRequest($client, 'GET', '/api/data/contexts', [
            'query' => ['search' => 'fill'],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertResponseIsSuccessful();

        $data1 = $response1->toArray();
        $data2 = $response2->toArray();

        // Both should return the same results due to case insensitivity
        $this->assertEquals(count($data1['member']), count($data2['member']));

        // Verify both return contexts with 'fill' in the name
        foreach ($data1['member'] as $item) {
            $this->assertStringContainsStringIgnoringCase('fill', $item['name']);
        }
        foreach ($data2['member'] as $item) {
            $this->assertStringContainsStringIgnoringCase('fill', $item['name']);
        }
    }

    public function testSearchFilterWithEmptyValueReturnsAllResults(): void
    {
        $client = self::createClient();

        $response = $this->apiRequest($client, 'GET', '/api/data/contexts', [
            'query' => ['search' => ''],
        ]);

        $this->assertResponseIsSuccessful();
        $data = $response->toArray();
        $this->assertArrayHasKey('member', $data);

        // Empty search should not filter results (same as no search parameter)
        $responseNoSearch = $this->apiRequest($client, 'GET', '/api/data/contexts');

        $dataNoSearch = $responseNoSearch->toArray();
        $this->assertEquals(count($dataNoSearch['member']), count($data['member']));
    }

    public function testSearchFilterParameterIsOptional(): void
    {
        $client = self::createClient();

        // Request without search parameter should work
        $response = $this->apiRequest($client, 'GET', '/api/data/contexts');

        $this->assertResponseIsSuccessful();
        $data = $response->toArray();
        $this->assertArrayHasKey('member', $data);
        $this->assertNotEmpty($data['member'], 'Should return all contexts when no search filter is applied');
    }

    public function testSearchFilterWithPartialMatches(): void
    {
        $client = self::createClient();

        $response = $this->apiRequest($client, 'GET', '/api/data/contexts', [
            'query' => ['search' => 'core'],
        ]);

        $this->assertResponseIsSuccessful();
        $data = $response->toArray();
        $this->assertArrayHasKey('member', $data);

        // Verify that results contain contexts with names containing 'core'
        foreach ($data['member'] as $item) {
            $this->assertStringContainsStringIgnoringCase('core', $item['name']);
        }
    }

    public function testSearchFilterWithMultipleDotsShouldSplitOnFirstOnly(): void
    {
        $client = self::createClient();

        $response = $this->apiRequest($client, 'GET', '/api/data/contexts', [
            'query' => ['search' => 'ME.fill.extra'],
        ]);

        $this->assertResponseIsSuccessful();
        $data = $response->toArray();
        $this->assertArrayHasKey('member', $data);

        // Should split only on first dot: site code 'ME' and name pattern 'fill.extra'
        foreach ($data['member'] as $item) {
            $this->assertStringEndsWith('ME', strtoupper($item['site']['code']));
            $this->assertStringContainsStringIgnoringCase('fill', $item['name']);
        }
    }
}
