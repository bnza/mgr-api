<?php

namespace App\Tests\Functional\Api\Resource\Filter;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Symfony\Bundle\Test\Client;
use App\Tests\Functional\Api\ApiTestProviderTrait;
use App\Tests\Functional\ApiTestRequestTrait;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class SearchPotteryFilterTest extends ApiTestCase
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

    public function testSearchFilterWithExistingInventory(): void
    {
        $client = self::createClient();

        // Get existing potteries to find one with an inventory we can test
        $potteries = $this->getPotteries();
        $this->assertNotEmpty($potteries, 'Should have at least one pottery for testing');

        $firstPottery = $potteries[0];
        $inventory = $firstPottery['inventory'];

        // Extract a portion of the inventory to search for
        $searchTerm = substr($inventory, 0, 3);

        $response = $this->apiRequest($client, 'GET', '/api/data/potteries', [
            'query' => ['search' => $searchTerm],
        ]);

        $this->assertResponseIsSuccessful();
        $data = $response->toArray();
        $this->assertArrayHasKey('member', $data);
        $this->assertNotEmpty($data['member']);

        // Verify that results contain potteries with inventory containing the search term
        foreach ($data['member'] as $item) {
            $this->assertStringContainsString($searchTerm, $item['inventory']);
        }
    }

    public function testSearchFilterWithPartialInventory(): void
    {
        $client = self::createClient();

        // Test with a partial search term that should match multiple results
        $response = $this->apiRequest($client, 'GET', '/api/data/potteries', [
            'query' => ['search' => '2023'],
        ]);

        $this->assertResponseIsSuccessful();
        $data = $response->toArray();
        $this->assertArrayHasKey('member', $data);

        // Verify that all results contain '2023' in their inventory
        foreach ($data['member'] as $item) {
            $this->assertStringContainsString('2023', $item['inventory']);
        }
    }

    public function testSearchFilterWithNonExistingInventory(): void
    {
        $client = self::createClient();

        // Test with a search term that shouldn't match any inventory
        $response = $this->apiRequest($client, 'GET', '/api/data/potteries', [
            'query' => ['search' => 'NONEXISTENT_INVENTORY_TERM'],
        ]);

        $this->assertResponseIsSuccessful();
        $data = $response->toArray();
        $this->assertArrayHasKey('member', $data);
        $this->assertEmpty($data['member'], 'Should return no results for non-existing inventory term');
    }

    public function testSearchFilterWithEmptyValue(): void
    {
        $client = self::createClient();

        // Test with empty search value - should return all potteries
        $responseWithoutSearch = $this->apiRequest($client, 'GET', '/api/data/potteries');

        $responseWithEmptySearch = $this->apiRequest($client, 'GET', '/api/data/potteries', [
            'query' => ['search' => ''],
        ]);

        $this->assertResponseIsSuccessful();

        $dataWithoutSearch = $responseWithoutSearch->toArray();
        $dataWithEmptySearch = $responseWithEmptySearch->toArray();

        // Both should return the same results
        $this->assertEquals($dataWithoutSearch['totalItems'], $dataWithEmptySearch['totalItems']);
    }

    public function testSearchFilterCanBeCombinedWithOtherFilters(): void
    {
        $client = self::createClient();

        // Get existing potteries to find one we can filter by
        $potteries = $this->getPotteries();
        $this->assertNotEmpty($potteries, 'Should have at least one pottery for testing');

        $firstPottery = $potteries[0];
        $inventory = $firstPottery['inventory'];
        $stratigraphicUnitId = $firstPottery['stratigraphicUnit']['id'];

        // Extract a portion of the inventory to search for
        $searchTerm = substr($inventory, 0, 3);

        // Combine search filter with stratigraphic unit filter
        $response = $this->apiRequest($client, 'GET', '/api/data/potteries', [
            'query' => [
                'search' => $searchTerm,
                'stratigraphicUnit' => $stratigraphicUnitId,
            ],
        ]);

        $this->assertResponseIsSuccessful();
        $data = $response->toArray();
        $this->assertArrayHasKey('member', $data);

        // Verify that results match both search term AND stratigraphic unit
        foreach ($data['member'] as $item) {
            $this->assertStringContainsString($searchTerm, $item['inventory']);
            $this->assertEquals($stratigraphicUnitId, $item['stratigraphicUnit']['id']);
        }
    }

    public function testSearchFilterCaseInsensitive(): void
    {
        $client = self::createClient();

        // Get existing potteries to find one with letters in inventory
        $potteries = $this->getPotteries();
        $this->assertNotEmpty($potteries, 'Should have at least one pottery for testing');

        // Find a pottery with letters in inventory
        $potteryWithLetters = null;
        foreach ($potteries as $pottery) {
            if (preg_match('/[A-Za-z]/', $pottery['inventory'])) {
                $potteryWithLetters = $pottery;
                break;
            }
        }

        if ($potteryWithLetters) {
            $inventory = $potteryWithLetters['inventory'];

            // Extract letters and test both upper and lower case
            if (preg_match('/[A-Za-z]+/', $inventory, $matches)) {
                $letterPart = $matches[0];

                // Test with lowercase
                $responseLower = $this->apiRequest($client, 'GET', '/api/data/potteries', [
                    'query' => ['search' => strtolower($letterPart)],
                ]);

                // Test with uppercase
                $responseUpper = $this->apiRequest($client, 'GET', '/api/data/potteries', [
                    'query' => ['search' => strtoupper($letterPart)],
                ]);

                $this->assertResponseIsSuccessful();

                $dataLower = $responseLower->toArray();
                $dataUpper = $responseUpper->toArray();

                // Both should return the same results (case insensitive)
                $this->assertEquals($dataLower['totalItems'], $dataUpper['totalItems']);

                // Verify that the original pottery is in both result sets
                $foundInLower = false;
                $foundInUpper = false;

                foreach ($dataLower['member'] as $item) {
                    if ($item['id'] === $potteryWithLetters['id']) {
                        $foundInLower = true;
                        break;
                    }
                }

                foreach ($dataUpper['member'] as $item) {
                    if ($item['id'] === $potteryWithLetters['id']) {
                        $foundInUpper = true;
                        break;
                    }
                }

                $this->assertTrue($foundInLower, 'Original pottery should be found with lowercase search');
                $this->assertTrue($foundInUpper, 'Original pottery should be found with uppercase search');
            }
        }
    }

    public function testSearchFilterWithSpecialCharacters(): void
    {
        $client = self::createClient();

        // Test with dots (which are literal in SQL LIKE, not wildcards)
        $response = $this->apiRequest($client, 'GET', '/api/data/potteries', [
            'query' => ['search' => '.'],
        ]);

        $this->assertResponseIsSuccessful();
        $data = $response->toArray();
        $this->assertArrayHasKey('member', $data);

        // All returned results should contain a literal dot in their inventory
        foreach ($data['member'] as $item) {
            $this->assertStringContainsString('.', $item['inventory']);
        }
    }
}
