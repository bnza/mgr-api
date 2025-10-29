<?php

namespace App\Tests\Functional\Api\Resource;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Tests\Functional\ApiTestRequestTrait;

class ApiResourcePersonViewTest extends ApiTestCase
{
    use ApiTestRequestTrait;

    public function testGetCollectionReturnsPersons(): void
    {
        $client = self::createClient();

        $response = $this->apiRequest($client, 'GET', '/api/list/persons');
        $this->assertSame(200, $response->getStatusCode());

        $data = $response->toArray();
        $this->assertIsArray($data['member'] ?? null, 'Expected collection "member" to be an array');
        $this->assertNotEmpty($data['member'], 'Expected collection to contain at least one item');

        $first = $data['member'][0];
        // Basic shape checks
        $this->assertArrayHasKey('value', $first);
        $this->assertArrayHasKey('@id', $first);
        $this->assertStringContainsString('/api/list/persons/', $first['@id']);
    }

    public function testGetItemByValue(): void
    {
        $client = self::createClient();

        // First, fetch the collection to get a valid existing value
        $collectionResponse = $this->apiRequest($client, 'GET', '/api/list/persons');
        $this->assertSame(200, $collectionResponse->getStatusCode());
        $collection = $collectionResponse->toArray();
        $this->assertNotEmpty($collection['member']);

        $first = $collection['member'][0];
        $iri = $first['@id'];

        // Request the item by its identifier (value)
        $itemResponse = $this->apiRequest($client, 'GET', $iri);
        $this->assertSame(200, $itemResponse->getStatusCode());
        $item = $itemResponse->toArray();

        $this->assertSame($first['value'], $item['value']);
        $this->assertArrayHasKey('@id', $item);
        $this->assertSame($first['@id'], $item['@id']);
    }
}
