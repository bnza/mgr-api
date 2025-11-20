<?php

namespace App\Tests\Functional\Api\Resource\Filter;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Symfony\Bundle\Test\Client;
use App\Tests\Functional\Api\ApiTestProviderTrait;
use App\Tests\Functional\ApiTestRequestTrait;
use PHPUnit\Framework\Attributes\DataProvider;

class SearchPropertyAliasFilterTest extends ApiTestCase
{
    use ApiTestRequestTrait;
    use ApiTestProviderTrait;

    private Client $client;

    protected function setUp(): void
    {
        parent::setUp();
        static::$alwaysBootKernel = false;
        $this->client = static::createClient();
    }

    #[DataProvider('provideCollections')]
    public function testSearchAliasReturnsSubset(string $collectionUrl, string $field): void
    {
        $client = static::createClient();

        // 1) Get full collection (no search)
        $response = $this->apiRequest($client, 'GET', $collectionUrl);
        $this->assertResponseIsSuccessful();
        $all = $response->toArray();
        $this->assertArrayHasKey('totalItems', $all, 'Collection response must have a totalItems key');
        $total = $all['totalItems'];
        $this->assertGreaterThan(0, $total, 'Fixtures should provide at least one item');

        // 2) Derive a search token from the first item
        $first = $all['member'][0];
        $this->assertArrayHasKey($field, $first, sprintf('Item should have a %s field', $field));
        $value = (string) $first[$field];
        $this->assertNotSame('', $value, sprintf('%s should not be empty', ucfirst($field)));

        // Pick a mid substring for partial search (case-insensitive)
        $start = (int) max(0, floor(strlen($value) / 3) - 1);
        $len = max(2, min(5, strlen($value) - $start));
        $token = substr($value, $start, $len);

        // 3) Search via alias (?search=... -> mapped field)
        $searched = $this->apiRequest($client, 'GET', $collectionUrl, [
            'query' => ['search' => $token],
        ]);
        $this->assertResponseIsSuccessful();
        $data = $searched->toArray();
        $this->assertArrayHasKey('totalItems', $data);
        $count = $data['totalItems'];

        // Should be a subset (<= total) and at least one result
        $this->assertGreaterThanOrEqual(1, $count, 'Search should return at least one matching item');
        $this->assertLessThanOrEqual($total, $count, 'Search should not return more than total');

        // Every returned item should contain the token in the target field (case-insensitive)
        foreach ($data['member'] as $item) {
            $this->assertArrayHasKey($field, $item);
            $this->assertNotFalse(stripos($item[$field], $token), sprintf('Expected "%s" to contain "%s" (case-insensitive)', $item[$field], $token));
        }
    }

    #[DataProvider('provideCollections')]
    public function testGibberishQueryReturnsEmptySet(string $collectionUrl, string $field): void
    {
        $client = static::createClient();

        $response = $this->apiRequest($client, 'GET', $collectionUrl, [
            'query' => ['search' => '___NO_MATCH_EXPECTED___12345'],
        ]);
        $this->assertResponseIsSuccessful();
        $data = $response->toArray();
        $this->assertArrayHasKey('member', $data);
        $this->assertCount(0, $data['member']);
    }

    public static function provideCollections(): array
    {
        return [
            'locations by name' => ['/api/data/vocabulary/history/locations', 'value'],
            'plants by value' => ['/api/vocabulary/history/plants', 'value'],
        ];
    }
}
