<?php

namespace App\Tests\Functional\Api\Resource\Filter\Join;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Tests\Functional\Api\ApiTestProviderTrait;
use App\Tests\Functional\ApiTestRequestTrait;

class SearchContextJoinNestedFilterTest extends ApiTestCase
{
    use ApiTestRequestTrait;
    use ApiTestProviderTrait;

    public function testSearchJoinNestedFilterByStratigraphicUnitYear(): void
    {
        $client = self::createClient();

        $response = $this->apiRequest($client, 'GET', '/api/data/contexts', [
            'query' => ['contextStratigraphicUnits.stratigraphicUnit.year' => '2024'],
        ]);

        $this->assertResponseIsSuccessful();
        $data = $response->toArray();
        $this->assertArrayHasKey('member', $data);

        // Verify that all returned contexts are associated with stratigraphic units having year 2024
        foreach ($data['member'] as $context) {
            // Make a request to get the context's associated stratigraphic units
            $contextResponse = $this->apiRequest($client, 'GET', "/api/data/contexts/{$context['id']}/stratigraphic_units");
            $this->assertResponseIsSuccessful();

            $contextData = $contextResponse->toArray();

            // Check that this context has at least one stratigraphic unit with year 2024
            $hasMatchingStratigraphicUnit = false;

            foreach ($contextData['member'] as $su) {
                if (2024 == $su['stratigraphicUnit']['year']) {
                    $hasMatchingStratigraphicUnit = true;
                    break;
                }
            }

            $this->assertTrue(
                $hasMatchingStratigraphicUnit,
                'Context should be associated with at least one stratigraphic unit with year 2024'
            );
        }
    }

    public function testRangeLessThanJoinNestedFilterByStratigraphicUnitNumber(): void
    {
        $client = self::createClient();

        $response = $this->apiRequest($client, 'GET', '/api/data/contexts', [
            'query' => ['contextStratigraphicUnits.stratigraphicUnit.number[lt]' => '500'],
        ]);

        $this->assertResponseIsSuccessful();
        $data = $response->toArray();
        $this->assertArrayHasKey('member', $data);

        // Verify that all returned contexts are associated with stratigraphic units having number 2024
        foreach ($data['member'] as $context) {
            // Make a request to get the context's associated stratigraphic units
            $contextResponse = $this->apiRequest($client, 'GET', "/api/data/contexts/{$context['id']}/stratigraphic_units");
            $this->assertResponseIsSuccessful();

            $contextData = $contextResponse->toArray();

            // Check that this context has at least one stratigraphic unit with number 2024
            $hasMatchingStratigraphicUnit = false;

            foreach ($contextData['member'] as $su) {
                if ((int) $su['stratigraphicUnit']['number'] < 500) {
                    $hasMatchingStratigraphicUnit = true;
                    break;
                }
            }

            $this->assertTrue(
                $hasMatchingStratigraphicUnit,
                'Context should be associated with at least one stratigraphic unit with number less than 500'
            );
        }
    }

    public function testUnaccentedJoinNestedFilterByStratigraphicUnitInterpretation(): void
    {
        $client = self::createClient();

        $response = $this->apiRequest($client, 'GET', '/api/data/contexts', [
            'query' => ['contextStratigraphicUnits.stratigraphicUnit.interpretation' => 'pit'],
        ]);

        $this->assertResponseIsSuccessful();
        $data = $response->toArray();
        $this->assertArrayHasKey('member', $data);

        // Verify that all returned contexts are associated with stratigraphic units having empty description
        foreach ($data['member'] as $context) {
            // Make a request to get the context's associated stratigraphic units
            $contextResponse = $this->apiRequest($client, 'GET', "/api/data/contexts/{$context['id']}/stratigraphic_units");
            $this->assertResponseIsSuccessful();

            $contextData = $contextResponse->toArray();

            $hasMatchingStratigraphicUnit = false;

            foreach ($contextData['member'] as $su) {
                if (str_contains(mb_strtolower($su['stratigraphicUnit']['interpretation']), 'pit')) {
                    $hasMatchingStratigraphicUnit = true;
                    break;
                }
            }

            $this->assertTrue(
                $hasMatchingStratigraphicUnit,
                'Context should be associated with at least one stratigraphic unit with empty description'
            );
        }
    }

    public function testExistsJoinNestedFilterByStratigraphicUnitDescription(): void
    {
        $client = self::createClient();

        $response = $this->apiRequest($client, 'GET', '/api/data/contexts', [
            'query' => ['exists[contextStratigraphicUnits.stratigraphicUnit.description]' => 'false'],
        ]);

        $this->assertResponseIsSuccessful();
        $data = $response->toArray();
        $this->assertArrayHasKey('member', $data);

        foreach ($data['member'] as $context) {
            // Make a request to get the context's associated stratigraphic units
            $contextResponse = $this->apiRequest($client, 'GET', "/api/data/contexts/{$context['id']}/stratigraphic_units");
            $this->assertResponseIsSuccessful();

            $contextData = $contextResponse->toArray();

            $hasMatchingStratigraphicUnit = false;

            foreach ($contextData['member'] as $su) {
                if (!$su['stratigraphicUnit']['description']) {
                    $hasMatchingStratigraphicUnit = true;
                    break;
                }
            }

            $this->assertTrue(
                $hasMatchingStratigraphicUnit,
                'Context should be associated with at least one stratigraphic unit with empty description'
            );
        }
    }

    public function testExistsJoinNestedFilterOpenApiSpecification(): void
    {
        $this->markTestSkipped('This test is skipped because custom nested filter parameters are not now deprecated.');
        $client = self::createClient();

        // Test the OpenAPI specification contains proper parameter names
        $response = $client->request('GET', '/api/docs.jsonopenapi', [
            'headers' => ['Accept' => 'application/vnd.openapi+json'],
        ]);
        $this->assertResponseIsSuccessful();

        $openApiSpec = $response->toArray();

        // Navigate to the contexts collection GET operation
        $this->assertArrayHasKey('paths', $openApiSpec);
        $this->assertArrayHasKey('/api/data/contexts', $openApiSpec['paths']);
        $this->assertArrayHasKey('get', $openApiSpec['paths']['/api/data/contexts']);

        $getOperation = $openApiSpec['paths']['/api/data/contexts']['get'];
        $this->assertArrayHasKey('parameters', $getOperation);

        // Extract parameter names
        $parameterNames = array_column($getOperation['parameters'], 'name');

        // Verify that the exists filter parameters are correctly named
        $this->assertContains('exists[stratigraphicUnit.description]', $parameterNames,
            'OpenAPI specification should contain exists[stratigraphicUnit.description] parameter');

        // Verify that the incorrect parameter names are NOT present
        $this->assertNotContains('exists[stratigraphicUnit.0]', $parameterNames,
            'OpenAPI specification should NOT contain exists[stratigraphicUnit.0] parameter');
    }
}
