<?php

namespace App\Tests\Functional\Api\Resource\Filter;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Symfony\Bundle\Test\Client;
use App\Tests\Functional\Api\ApiTestProviderTrait;
use App\Tests\Functional\ApiTestRequestTrait;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class SearchZooBoneFilterTest extends ApiTestCase
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

    /**
     * Get fixture zoo bones without creating new ones.
     */
    protected function getFixtureZooBones(array $queryParams = []): array
    {
        $client = self::createClient();
        $url = '/api/data/zoo/bones';
        if (!empty($queryParams)) {
            $url .= '?'.http_build_query($queryParams);
        }
        $response = $this->apiRequest($client, 'GET', $url);
        $this->assertSame(200, $response->getStatusCode());

        return $response->toArray()['member'];
    }

    public function testSearchFilterWithNumericChunk(): void
    {
        $client = self::createClient();

        // Get first zoo bone to extract valid ID for testing
        $zooBones = $this->getFixtureZooBones(['itemsPerPage' => 1]);
        $this->assertNotEmpty($zooBones, 'No zoo bones found in fixtures');

        $firstZooBone = $zooBones[0];
        $fullId = (string) $firstZooBone['id'];

        // Use last 2-3 digits of the ID for testing
        $searchPattern = substr($fullId, -2);

        $response = $this->apiRequest($client, 'GET', '/api/data/zoo/bones', [
            'query' => ['search' => $searchPattern],
        ]);

        $this->assertResponseIsSuccessful();
        $data = $response->toArray();
        $this->assertArrayHasKey('member', $data);
        $this->assertNotEmpty($data['member'], "No results found for ID pattern: {$searchPattern}");

        // Verify that results contain zoo bones with IDs ending with the search pattern
        foreach ($data['member'] as $item) {
            $this->assertStringEndsWith($searchPattern, (string) $item['id']);
        }
    }

    public function testSearchFilterWithNonNumericChunk(): void
    {
        $client = self::createClient();

        // Get first zoo bone to extract valid site code for testing
        $zooBones = $this->getFixtureZooBones(['itemsPerPage' => 1]);
        $this->assertNotEmpty($zooBones, 'No zoo bones found in fixtures');

        $firstZooBone = $zooBones[0];
        $fullSiteCode = $firstZooBone['stratigraphicUnit']['site']['code'];

        // Use last 1-2 characters of site code for testing
        $searchPattern = substr($fullSiteCode, -1);

        $response = $this->apiRequest($client, 'GET', '/api/data/zoo/bones', [
            'query' => ['search' => $searchPattern],
        ]);

        $this->assertResponseIsSuccessful();
        $data = $response->toArray();
        $this->assertArrayHasKey('member', $data);
        $this->assertNotEmpty($data['member'], "No results found for site code pattern: {$searchPattern}");

        // Verify that results contain zoo bones with site codes ending with the search pattern
        foreach ($data['member'] as $item) {
            $this->assertStringEndsWith($searchPattern, strtoupper($item['stratigraphicUnit']['site']['code']));
        }
    }

    public function testSearchFilterWithTwoChunksNumericAndText(): void
    {
        $client = self::createClient();

        // Get first zoo bone to extract valid data for testing
        $zooBones = $this->getFixtureZooBones(['itemsPerPage' => 1]);
        $this->assertNotEmpty($zooBones, 'No zoo bones found in fixtures');

        $firstZooBone = $zooBones[0];
        $fullId = (string) $firstZooBone['id'];
        $fullSiteCode = $firstZooBone['stratigraphicUnit']['site']['code'];

        // Use patterns that should match the first zoo bone
        $sitePattern = substr($fullSiteCode, -1);
        $idPattern = substr($fullId, -2);

        $response = $this->apiRequest($client, 'GET', '/api/data/zoo/bones', [
            'query' => ['search' => "{$sitePattern} {$idPattern}"],
        ]);

        $this->assertResponseIsSuccessful();
        $data = $response->toArray();
        $this->assertArrayHasKey('member', $data);

        // Verify that results match both site code ending with pattern AND ID ending with pattern
        foreach ($data['member'] as $item) {
            $this->assertStringEndsWith($sitePattern, strtoupper($item['stratigraphicUnit']['site']['code']));
            $this->assertStringEndsWith($idPattern, (string) $item['id']);
        }

        // Verify the original zoo bone is in the results
        $foundOriginal = false;
        foreach ($data['member'] as $item) {
            if ($item['id'] === $firstZooBone['id']) {
                $foundOriginal = true;
                break;
            }
        }
        $this->assertTrue($foundOriginal, 'Original zoo bone should be found in filtered results');
    }

    public function testSearchFilterWithTwoChunksTextAndNumeric(): void
    {
        $client = self::createClient();

        // Get first zoo bone to extract valid data for testing
        $zooBones = $this->getFixtureZooBones(['itemsPerPage' => 1]);
        $this->assertNotEmpty($zooBones, 'No zoo bones found in fixtures');

        $firstZooBone = $zooBones[0];
        $fullId = (string) $firstZooBone['id'];
        $fullSiteCode = $firstZooBone['stratigraphicUnit']['site']['code'];

        // Use patterns with hyphen separator
        $sitePattern = substr($fullSiteCode, -1);
        $idPattern = substr($fullId, -2);

        $response = $this->apiRequest($client, 'GET', '/api/data/zoo/bones', [
            'query' => ['search' => "{$sitePattern}-{$idPattern}"],
        ]);

        $this->assertResponseIsSuccessful();
        $data = $response->toArray();
        $this->assertArrayHasKey('member', $data);

        // Verify that results match both patterns
        foreach ($data['member'] as $item) {
            $this->assertStringEndsWith($sitePattern, strtoupper($item['stratigraphicUnit']['site']['code']));
            $this->assertStringEndsWith($idPattern, (string) $item['id']);
        }
    }

    public function testSearchFilterWithTwoTextChunks(): void
    {
        $client = self::createClient();

        // Get first zoo bone to extract valid site code for testing
        $zooBones = $this->getFixtureZooBones(['itemsPerPage' => 1]);
        $this->assertNotEmpty($zooBones, 'No zoo bones found in fixtures');

        $firstZooBone = $zooBones[0];
        $fullSiteCode = $firstZooBone['stratigraphicUnit']['site']['code'];

        // Use different parts of site code that can't both match
        $pattern1 = substr($fullSiteCode, -2, 1); // Second from last char
        $pattern2 = substr($fullSiteCode, -1);    // Last char

        $response = $this->apiRequest($client, 'GET', '/api/data/zoo/bones', [
            'query' => ['search' => "{$pattern1} {$pattern2}"],
        ]);

        $this->assertResponseIsSuccessful();
        $data = $response->toArray();
        $this->assertArrayHasKey('member', $data);

        // This should return empty results since no single site code can end with two different patterns
        $this->assertEmpty($data['member'], 'Two different text patterns should return no results');
    }

    public function testSearchFilterWithNonWordSeparators(): void
    {
        $client = self::createClient();

        // Get first zoo bone to extract valid data for testing
        $zooBones = $this->getFixtureZooBones(['itemsPerPage' => 1]);
        $this->assertNotEmpty($zooBones, 'No zoo bones found in fixtures');

        $firstZooBone = $zooBones[0];
        $fullId = (string) $firstZooBone['id'];
        $fullSiteCode = $firstZooBone['stratigraphicUnit']['site']['code'];

        $sitePattern = substr($fullSiteCode, -1);
        $idPattern = substr($fullId, -2);

        // Test with various non-word separators
        $separators = ['-', '_', '.', '/', ':', ';', ','];

        foreach ($separators as $separator) {
            $response = $this->apiRequest($client, 'GET', '/api/data/zoo/bones', [
                'query' => ['search' => "{$sitePattern}{$separator}{$idPattern}"],
            ]);

            $this->assertResponseIsSuccessful();
            $data = $response->toArray();
            $this->assertArrayHasKey('member', $data);

            // Should split on the separator and match both patterns
            foreach ($data['member'] as $item) {
                $this->assertStringEndsWith($sitePattern, strtoupper($item['stratigraphicUnit']['site']['code']));
                $this->assertStringEndsWith($idPattern, (string) $item['id']);
            }
        }
    }

    public function testSearchFilterIgnoresMoreThanTwoChunks(): void
    {
        $client = self::createClient();

        // Get first zoo bone to extract valid data for testing
        $zooBones = $this->getFixtureZooBones(['itemsPerPage' => 1]);
        $this->assertNotEmpty($zooBones, 'No zoo bones found in fixtures');

        $firstZooBone = $zooBones[0];
        $fullId = (string) $firstZooBone['id'];
        $fullSiteCode = $firstZooBone['stratigraphicUnit']['site']['code'];

        $sitePattern = substr($fullSiteCode, -1);
        $idPattern = substr($fullId, -2);

        $response = $this->apiRequest($client, 'GET', '/api/data/zoo/bones', [
            'query' => ['search' => "{$sitePattern} {$idPattern} extra ignored"],
        ]);

        $this->assertResponseIsSuccessful();
        $data = $response->toArray();
        $this->assertArrayHasKey('member', $data);

        // Should only use first two chunks, ignoring 'extra' and 'ignored'
        foreach ($data['member'] as $item) {
            $this->assertStringEndsWith($sitePattern, strtoupper($item['stratigraphicUnit']['site']['code']));
            $this->assertStringEndsWith($idPattern, (string) $item['id']);
        }

        // Verify the original zoo bone is in the results
        $foundOriginal = false;
        foreach ($data['member'] as $item) {
            if ($item['id'] === $firstZooBone['id']) {
                $foundOriginal = true;
                break;
            }
        }
        $this->assertTrue($foundOriginal, 'Original zoo bone should be found in filtered results');
    }

    public function testSearchFilterWithInvalidCombinationReturnsEmptySet(): void
    {
        $client = self::createClient();

        // Test combination that should not match any fixtures
        $response = $this->apiRequest($client, 'GET', '/api/data/zoo/bones', [
            'query' => ['search' => 'NONEXISTENT 99999999'],
        ]);

        $this->assertResponseIsSuccessful();
        $data = $response->toArray();
        $this->assertArrayHasKey('member', $data);
        $this->assertEmpty($data['member'], 'Non-matching combination should return empty results');
    }

    public function testSearchFilterWithEmptyValueReturnsAllResults(): void
    {
        $client = self::createClient();

        $response = $this->apiRequest($client, 'GET', '/api/data/zoo/bones', [
            'query' => ['search' => ''],
        ]);

        $this->assertResponseIsSuccessful();
        $data = $response->toArray();
        $this->assertArrayHasKey('member', $data);

        // Empty search should not filter results (same as no search parameter)
        $responseNoSearch = $this->apiRequest($client, 'GET', '/api/data/zoo/bones');
        $dataNoSearch = $responseNoSearch->toArray();
        $this->assertEquals(count($dataNoSearch['member']), count($data['member']));
    }

    public function testSearchFilterParameterIsOptional(): void
    {
        $client = self::createClient();

        // Request without search parameter should work
        $response = $this->apiRequest($client, 'GET', '/api/data/zoo/bones');

        $this->assertResponseIsSuccessful();
        $data = $response->toArray();
        $this->assertArrayHasKey('member', $data);
        $this->assertNotEmpty($data['member'], 'Should return all zoo bones when no search filter is applied');
    }

    public function testSearchFilterHandlesWhitespaceAndEmptyChunks(): void
    {
        $client = self::createClient();

        // Get first zoo bone to extract valid data for testing
        $zooBones = $this->getFixtureZooBones(['itemsPerPage' => 1]);
        $this->assertNotEmpty($zooBones, 'No zoo bones found in fixtures');

        $firstZooBone = $zooBones[0];
        $fullId = (string) $firstZooBone['id'];
        $fullSiteCode = $firstZooBone['stratigraphicUnit']['site']['code'];

        $sitePattern = substr($fullSiteCode, -1);
        $idPattern = substr($fullId, -2);

        // Test with extra whitespace
        $response = $this->apiRequest($client, 'GET', '/api/data/zoo/bones', [
            'query' => ['search' => "  {$sitePattern}   {$idPattern}  "],
        ]);

        $this->assertResponseIsSuccessful();
        $data = $response->toArray();
        $this->assertArrayHasKey('member', $data);

        // Should properly trim and handle the chunks
        foreach ($data['member'] as $item) {
            $this->assertStringEndsWith($sitePattern, strtoupper($item['stratigraphicUnit']['site']['code']));
            $this->assertStringEndsWith($idPattern, (string) $item['id']);
        }
    }

    public function testSearchFilterCanBeCombinedWithOtherFilters(): void
    {
        $client = self::createClient();

        // Get first zoo bone to extract valid data for testing
        $zooBones = $this->getFixtureZooBones(['itemsPerPage' => 1]);
        $this->assertNotEmpty($zooBones, 'No zoo bones found in fixtures');

        $firstZooBone = $zooBones[0];
        $fullSiteCode = $firstZooBone['stratigraphicUnit']['site']['code'];
        $sitePattern = substr($fullSiteCode, -1);

        $response = $this->apiRequest($client, 'GET', '/api/data/zoo/bones', [
            'query' => [
                'search' => $sitePattern,
                'itemsPerPage' => 5,
            ],
        ]);

        $this->assertResponseIsSuccessful();
        $data = $response->toArray();
        $this->assertArrayHasKey('member', $data);

        // Verify search filter works in combination with other parameters
        foreach ($data['member'] as $item) {
            $this->assertStringEndsWith($sitePattern, strtoupper($item['stratigraphicUnit']['site']['code']));
        }

        // Verify pagination is applied
        $this->assertLessThanOrEqual(5, count($data['member']));
    }
}
