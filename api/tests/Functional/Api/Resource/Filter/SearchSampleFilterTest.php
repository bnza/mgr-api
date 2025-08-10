<?php

namespace App\Tests\Functional\Api\Resource\Filter;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Symfony\Bundle\Test\Client;
use App\Tests\Functional\Api\ApiTestProviderTrait;
use App\Tests\Functional\ApiTestRequestTrait;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class SearchSampleFilterTest extends ApiTestCase
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
        $existentSample = $this->getSamples()[0];

        $this->assertNotNull($existentSample);

        // Using first getSamples entry site code
        $response = $this->apiRequest($client, 'GET', '/api/data/samples', [
            'query' => ['search' => $existentSample['site']['code']],
        ]);

        $this->assertResponseIsSuccessful();
        $data = $response->toArray();
        $this->assertArrayHasKey('member', $data);

        // Verify that results contain samples from sites with matching code
        foreach ($data['member'] as $item) {
            $this->assertStringContainsString($existentSample['site']['code'], strtoupper($item['site']['code']));
        }
    }

    public function testSearchFilterWithOneChunkNumeric(): void
    {
        $client = self::createClient();
        $existentSample = $this->getSamples()[0];

        $this->assertNotNull($existentSample);

        // Using first getSamples entry sample number
        $response = $this->apiRequest($client, 'GET', '/api/data/samples', [
            'query' => ['search' => (string) $existentSample['number']],
        ]);

        $this->assertResponseIsSuccessful();
        $data = $response->toArray();
        $this->assertArrayHasKey('member', $data);

        // Verify that results contain samples with matching sample number
        foreach ($data['member'] as $item) {
            $this->assertStringContainsString((string) $existentSample['number'], (string) $item['number']);
        }
    }

    public function testSearchFilterWithTwoChunksBothString(): void
    {
        $client = self::createClient();
        $existentSample = $this->getSamples()[0];

        $this->assertNotNull($existentSample);

        // Using site code and type code
        $response = $this->apiRequest($client, 'GET', '/api/data/samples', [
            'query' => ['search' => $existentSample['site']['code'].' '.$existentSample['type']['code']],
        ]);

        $this->assertResponseIsSuccessful();
        $data = $response->toArray();
        $this->assertArrayHasKey('member', $data);

        // Verify that results match both site code and type code criteria
        foreach ($data['member'] as $item) {
            $this->assertStringContainsString($existentSample['site']['code'], strtoupper($item['site']['code']));
            $this->assertStringContainsString($existentSample['type']['code'], strtoupper($item['type']['code']));
        }
    }

    public function testSearchFilterWithTwoChunksBothNumeric(): void
    {
        $client = self::createClient();
        $existentSample = $this->getSamples()[0];

        $this->assertNotNull($existentSample);

        // Using sample year and sample number
        $response = $this->apiRequest($client, 'GET', '/api/data/samples', [
            'query' => ['search' => $existentSample['year'].' '.$existentSample['number']],
        ]);

        $this->assertResponseIsSuccessful();
        $data = $response->toArray();
        $this->assertArrayHasKey('member', $data);

        // Verify that results match both sample year and sample number criteria
        foreach ($data['member'] as $item) {
            $this->assertStringContainsString((string) $existentSample['year'], (string) $item['year']);
            $this->assertStringContainsString((string) $existentSample['number'], (string) $item['number']);
        }
    }

    public function testSearchFilterWithTwoChunksStringAndNumeric(): void
    {
        $client = self::createClient();
        $existentSample = $this->getSamples()[0];

        $this->assertNotNull($existentSample);

        // Using site code and sample number
        $response = $this->apiRequest($client, 'GET', '/api/data/samples', [
            'query' => ['search' => $existentSample['site']['code'].' '.$existentSample['number']],
        ]);

        $this->assertResponseIsSuccessful();
        $data = $response->toArray();
        $this->assertArrayHasKey('member', $data);

        // Verify that results match both site code and sample number criteria
        foreach ($data['member'] as $item) {
            $this->assertStringContainsString($existentSample['site']['code'], strtoupper($item['site']['code']));
            $this->assertStringContainsString((string) $existentSample['number'], (string) $item['number']);
        }
    }

    public function testSearchFilterWithThreeChunksThreeStrings(): void
    {
        $client = self::createClient();
        $existentSample = $this->getSamples()[0];

        $this->assertNotNull($existentSample);

        // Using site code, type code, and extra string (should discard third)
        $response = $this->apiRequest($client, 'GET', '/api/data/samples', [
            'query' => ['search' => $existentSample['site']['code'].' '.$existentSample['type']['code'].' EXTRA'],
        ]);

        $this->assertResponseIsSuccessful();
        $data = $response->toArray();
        $this->assertArrayHasKey('member', $data);

        // Verify that results match site code and type code (third string should be discarded)
        foreach ($data['member'] as $item) {
            $this->assertStringContainsString($existentSample['site']['code'], strtoupper($item['site']['code']));
            $this->assertStringContainsString($existentSample['type']['code'], strtoupper($item['type']['code']));
        }
    }

    public function testSearchFilterWithThreeChunksTwoStringOneNumeric(): void
    {
        $client = self::createClient();
        $existentSample = $this->getSamples()[0];

        $this->assertNotNull($existentSample);

        // Using site code, type code, and sample number
        $response = $this->apiRequest($client, 'GET', '/api/data/samples', [
            'query' => ['search' => $existentSample['site']['code'].' '.$existentSample['type']['code'].' '.$existentSample['number']],
        ]);

        $this->assertResponseIsSuccessful();
        $data = $response->toArray();
        $this->assertArrayHasKey('member', $data);

        // Verify that results match all three criteria
        foreach ($data['member'] as $item) {
            $this->assertStringContainsString($existentSample['site']['code'], strtoupper($item['site']['code']));
            $this->assertStringContainsString($existentSample['type']['code'], strtoupper($item['type']['code']));
            $this->assertStringContainsString((string) $existentSample['number'], (string) $item['number']);
        }
    }

    public function testSearchFilterWithFourChunksTwoStringTwoNumeric(): void
    {
        $client = self::createClient();
        $existentSample = $this->getSamples()[0];

        $this->assertNotNull($existentSample);

        // Using all four fields: site code, type code, sample year, sample number
        $response = $this->apiRequest($client, 'GET', '/api/data/samples', [
            'query' => ['search' => $existentSample['site']['code'].' '.$existentSample['type']['code'].' '.$existentSample['year'].' '.$existentSample['number']],
        ]);

        $this->assertResponseIsSuccessful();
        $data = $response->toArray();
        $this->assertArrayHasKey('member', $data);

        // Verify that results match all four criteria
        foreach ($data['member'] as $item) {
            $this->assertStringContainsString($existentSample['site']['code'], strtoupper($item['site']['code']));
            $this->assertStringContainsString($existentSample['type']['code'], strtoupper($item['type']['code']));
            $this->assertStringContainsString((string) $existentSample['year'], (string) $item['year']);
            $this->assertStringContainsString((string) $existentSample['number'], (string) $item['number']);
        }
    }

    public function testSearchFilterWithFourChunksThreeStringOneNumeric(): void
    {
        $client = self::createClient();
        $existentSample = $this->getSamples()[0];

        $this->assertNotNull($existentSample);

        // Using site code, type code, extra string, and sample number (should discard third string)
        $response = $this->apiRequest($client, 'GET', '/api/data/samples', [
            'query' => ['search' => $existentSample['site']['code'].' '.$existentSample['type']['code'].' EXTRA '.$existentSample['number']],
        ]);

        $this->assertResponseIsSuccessful();
        $data = $response->toArray();
        $this->assertArrayHasKey('member', $data);

        // Verify that results match site code, type code, and sample number (third string discarded)
        foreach ($data['member'] as $item) {
            $this->assertStringContainsString($existentSample['site']['code'], strtoupper($item['site']['code']));
            $this->assertStringContainsString($existentSample['type']['code'], strtoupper($item['type']['code']));
            $this->assertStringContainsString((string) $existentSample['number'], (string) $item['number']);
        }
    }

    public function testSearchFilterWithVariousDelimiters(): void
    {
        $client = self::createClient();
        $existentSample = $this->getSamples()[0];

        $this->assertNotNull($existentSample);

        // Test with dot delimiter
        $response1 = $this->apiRequest($client, 'GET', '/api/data/samples', [
            'query' => ['search' => $existentSample['site']['code'].'.'.$existentSample['number']],
        ]);

        // Test with space delimiter
        $response2 = $this->apiRequest($client, 'GET', '/api/data/samples', [
            'query' => ['search' => $existentSample['site']['code'].' '.$existentSample['number']],
        ]);

        // Test with hyphen delimiter
        $response3 = $this->apiRequest($client, 'GET', '/api/data/samples', [
            'query' => ['search' => $existentSample['site']['code'].'-'.$existentSample['number']],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertResponseIsSuccessful();
        $this->assertResponseIsSuccessful();

        $data1 = $response1->toArray();
        $data2 = $response2->toArray();
        $data3 = $response3->toArray();

        // All should return the same results since they use the same chunks
        $this->assertEquals($data1['member'], $data2['member']);
        $this->assertEquals($data2['member'], $data3['member']);
    }

    public function testSearchFilterWithEmptyValueReturnsAllResults(): void
    {
        $client = self::createClient();

        $response = $this->apiRequest($client, 'GET', '/api/data/samples', [
            'query' => ['search' => ''],
        ]);

        $this->assertResponseIsSuccessful();
        $data = $response->toArray();
        $this->assertArrayHasKey('member', $data);

        // Empty search should not filter results (same as no search parameter)
        $responseNoSearch = $this->apiRequest($client, 'GET', '/api/data/samples');

        $dataNoSearch = $responseNoSearch->toArray();
        $this->assertEquals($dataNoSearch['member'], $data['member']);
    }

    public function testSearchFilterParameterIsOptional(): void
    {
        $client = self::createClient();

        // Request without search parameter should work
        $response = $this->apiRequest($client, 'GET', '/api/data/samples');

        $this->assertResponseIsSuccessful();
        $data = $response->toArray();
        $this->assertArrayHasKey('member', $data);
    }

    public function testSearchFilterWithTooManyChunksIgnored(): void
    {
        $client = self::createClient();

        // Test with more than 4 chunks (should be ignored)
        $response = $this->apiRequest($client, 'GET', '/api/data/samples', [
            'query' => ['search' => 'A B C D E F G'],
        ]);

        $this->assertResponseIsSuccessful();
        $data = $response->toArray();
        $this->assertArrayHasKey('member', $data);

        // Should return same as no filter since too many chunks are ignored
        $responseNoSearch = $this->apiRequest($client, 'GET', '/api/data/samples');
        $dataNoSearch = $responseNoSearch->toArray();
        $this->assertEquals($dataNoSearch['member'], $data['member']);
    }

    public function testSearchFilterWithPartialMatches(): void
    {
        $client = self::createClient();
        $existentSample = $this->getSamples()[0];

        $this->assertNotNull($existentSample);

        // Test partial site code match
        $partialSiteCode = substr($existentSample['site']['code'], 0, -1); // Remove last character

        $response = $this->apiRequest($client, 'GET', '/api/data/samples', [
            'query' => ['search' => $partialSiteCode],
        ]);

        $this->assertResponseIsSuccessful();
        $data = $response->toArray();
        $this->assertArrayHasKey('member', $data);

        // Verify that results contain samples with site codes containing the partial match
        foreach ($data['member'] as $item) {
            $this->assertStringContainsString($partialSiteCode, strtoupper($item['site']['code']));
        }
    }
}
