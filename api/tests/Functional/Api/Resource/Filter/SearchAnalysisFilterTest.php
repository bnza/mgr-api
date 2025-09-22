<?php

namespace App\Tests\Functional\Api\Resource\Filter;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Symfony\Bundle\Test\Client;
use App\Tests\Functional\Api\ApiTestProviderTrait;
use App\Tests\Functional\ApiTestRequestTrait;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class SearchAnalysisFilterTest extends ApiTestCase
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

        $response = $this->apiRequest($client, 'GET', '/api/data/analyses', [
            'query' => ['search' => 'XRF'],
        ]);

        $this->assertResponseIsSuccessful();
        $data = $response->toArray();
        $this->assertArrayHasKey('member', $data);

        // Verify that results contain analyses with type code OR identifier containing 'XRF'
        foreach ($data['member'] as $item) {
            $containsInTypeCode = false !== stripos($item['type']['code'], 'XRF');
            $containsInIdentifier = false !== stripos($item['identifier'], 'XRF');
            $this->assertTrue($containsInTypeCode || $containsInIdentifier);
        }
    }

    public function testSearchFilterCanBeCombinedWithOrderFilter(): void
    {
        $client = self::createClient();

        $response = $this->apiRequest($client, 'GET', '/api/data/analyses', [
            'query' => ['search' => 'ADNA', 'order[identifier]' => 'asc'],
        ]);

        $this->assertResponseIsSuccessful();
        $data = $response->toArray();
        $this->assertArrayHasKey('member', $data);

        // Verify that results contain analyses with type code OR identifier containing 'ADNA'
        foreach ($data['member'] as $item) {
            $containsInTypeCode = false !== stripos($item['type']['code'], 'ADNA');
            $containsInIdentifier = false !== stripos($item['identifier'], 'ADNA');
            $this->assertTrue($containsInTypeCode || $containsInIdentifier);
        }
    }

    public function testSearchFilterWithTwoChunksTypeCodeAndIdentifier(): void
    {
        $client = self::createClient();

        $response = $this->apiRequest($client, 'GET', '/api/data/analyses', [
            'query' => ['search' => 'ADNA.ME102'],
        ]);

        $this->assertResponseIsSuccessful();
        $data = $response->toArray();
        $this->assertArrayHasKey('member', $data);

        // Verify that results match both type code containing 'ADNA' AND identifier containing 'ME102'
        foreach ($data['member'] as $item) {
            $this->assertStringContainsStringIgnoringCase('ADNA', $item['type']['code']);
            $this->assertStringContainsStringIgnoringCase('ME102', $item['identifier']);
        }
    }

    public function testSearchFilterWithTwoChunksPartialTypeCodeAndIdentifier(): void
    {
        $client = self::createClient();

        $response = $this->apiRequest($client, 'GET', '/api/data/analyses', [
            'query' => ['search' => 'ZO.FAA'],
        ]);

        $this->assertResponseIsSuccessful();
        $data = $response->toArray();
        $this->assertArrayHasKey('member', $data);

        // Verify that results match both type code containing 'ZO' AND identifier containing 'FAA'
        foreach ($data['member'] as $item) {
            $this->assertStringContainsStringIgnoringCase('ZO', $item['type']['code']);
            $this->assertStringContainsStringIgnoringCase('FAA', $item['identifier']);
        }
    }

    public function testSearchFilterWithTwoChunksNumericInIdentifier(): void
    {
        $client = self::createClient();

        $response = $this->apiRequest($client, 'GET', '/api/data/analyses', [
            'query' => ['search' => 'SEM.25'],
        ]);

        $this->assertResponseIsSuccessful();
        $data = $response->toArray();
        $this->assertArrayHasKey('member', $data);

        // Verify that results match both type code containing 'SEM' AND identifier containing '25'
        foreach ($data['member'] as $item) {
            $this->assertStringContainsStringIgnoringCase('SEM', $item['type']['code']);
            $this->assertStringContainsString('25', $item['identifier']);
        }
    }

    public function testSearchFilterWithInvalidCombinationReturnsEmptySet(): void
    {
        $client = self::createClient();

        // Test combination that should not match any fixtures
        $response = $this->apiRequest($client, 'GET', '/api/data/analyses', [
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
        $response1 = $this->apiRequest($client, 'GET', '/api/data/analyses', [
            'query' => ['search' => 'XRF'],
        ]);

        // Test lowercase search term
        $response2 = $this->apiRequest($client, 'GET', '/api/data/analyses', [
            'query' => ['search' => 'xrf'],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertResponseIsSuccessful();

        $data1 = $response1->toArray();
        $data2 = $response2->toArray();

        // Both should return the same results due to case insensitivity
        $this->assertEquals(count($data1['member']), count($data2['member']));

        // Verify both return analyses with 'XRF' in the type code or identifier
        foreach ($data1['member'] as $item) {
            $containsInTypeCode = false !== stripos($item['type']['code'], 'XRF');
            $containsInIdentifier = false !== stripos($item['identifier'], 'XRF');
            $this->assertTrue($containsInTypeCode || $containsInIdentifier);
        }
        foreach ($data2['member'] as $item) {
            $containsInTypeCode = false !== stripos($item['type']['code'], 'XRF');
            $containsInIdentifier = false !== stripos($item['identifier'], 'XRF');
            $this->assertTrue($containsInTypeCode || $containsInIdentifier);
        }
    }

    public function testSearchFilterWithEmptyValueReturnsAllResults(): void
    {
        $client = self::createClient();

        $response = $this->apiRequest($client, 'GET', '/api/data/analyses', [
            'query' => ['search' => ''],
        ]);

        $this->assertResponseIsSuccessful();
        $data = $response->toArray();
        $this->assertArrayHasKey('member', $data);

        // Empty search should not filter results (same as no search parameter)
        $responseNoSearch = $this->apiRequest($client, 'GET', '/api/data/analyses');

        $dataNoSearch = $responseNoSearch->toArray();
        $this->assertEquals(count($dataNoSearch['member']), count($data['member']));
    }

    public function testSearchFilterParameterIsOptional(): void
    {
        $client = self::createClient();

        // Request without search parameter should work
        $response = $this->apiRequest($client, 'GET', '/api/data/analyses');

        $this->assertResponseIsSuccessful();
        $data = $response->toArray();
        $this->assertArrayHasKey('member', $data);
        $this->assertNotEmpty($data['member'], 'Should return all analyses when no search filter is applied');
    }

    public function testSearchFilterWithPartialMatches(): void
    {
        $client = self::createClient();

        $response = $this->apiRequest($client, 'GET', '/api/data/analyses', [
            'query' => ['search' => 'micro'],
        ]);

        $this->assertResponseIsSuccessful();
        $data = $response->toArray();
        $this->assertArrayHasKey('member', $data);

        // Verify that results contain analyses with type code or identifier containing 'micro'
        foreach ($data['member'] as $item) {
            $containsInTypeCode = false !== stripos($item['type']['code'], 'micro');
            $containsInIdentifier = false !== stripos($item['identifier'], 'micro');
            $this->assertTrue($containsInTypeCode || $containsInIdentifier);
        }
    }

    public function testSearchFilterWithMultipleDotsShouldSplitOnFirstOnly(): void
    {
        $client = self::createClient();

        $response = $this->apiRequest($client, 'GET', '/api/data/analyses', [
            'query' => ['search' => 'ADNA.2025.ME102'],
        ]);

        $this->assertResponseIsSuccessful();
        $data = $response->toArray();
        $this->assertArrayHasKey('member', $data);

        // Should split only on first dot: type code 'ADNA' and identifier pattern '2025.ME102'
        foreach ($data['member'] as $item) {
            $this->assertStringContainsStringIgnoringCase('ADNA', $item['type']['code']);
            $this->assertStringContainsStringIgnoringCase('2025', $item['identifier']);
        }
    }

    public function testSearchFilterWithEmptyTypeCodeOnlyIdentifier(): void
    {
        $client = self::createClient();

        $response = $this->apiRequest($client, 'GET', '/api/data/analyses', [
            'query' => ['search' => '.optical'],
        ]);

        $this->assertResponseIsSuccessful();
        $data = $response->toArray();
        $this->assertArrayHasKey('member', $data);

        // Should search only by identifier when type code part is empty
        foreach ($data['member'] as $item) {
            $this->assertStringContainsStringIgnoringCase('optical', $item['identifier']);
        }
    }

    public function testSearchFilterWithTypeCodeOnlyEmptyIdentifier(): void
    {
        $client = self::createClient();

        $response = $this->apiRequest($client, 'GET', '/api/data/analyses', [
            'query' => ['search' => 'SEM.'],
        ]);

        $this->assertResponseIsSuccessful();
        $data = $response->toArray();
        $this->assertArrayHasKey('member', $data);

        // Should search only by type code when identifier part is empty
        foreach ($data['member'] as $item) {
            $this->assertStringContainsStringIgnoringCase('SEM', $item['type']['code']);
        }
    }

    public function testSearchFilterWithYear(): void
    {
        $client = self::createClient();

        $response = $this->apiRequest($client, 'GET', '/api/data/analyses', [
            'query' => ['search' => '2025'],
        ]);

        $this->assertResponseIsSuccessful();
        $data = $response->toArray();
        $this->assertArrayHasKey('member', $data);

        // Verify that results contain analyses with '2025' in type code or identifier
        foreach ($data['member'] as $item) {
            $containsInTypeCode = false !== stripos($item['type']['code'], '2025');
            $containsInIdentifier = false !== stripos($item['identifier'], '2025');
            $this->assertTrue($containsInTypeCode || $containsInIdentifier);
        }
    }
}
