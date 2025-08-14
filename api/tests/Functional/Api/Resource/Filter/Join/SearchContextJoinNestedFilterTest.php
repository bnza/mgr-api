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
            'query' => ['stratigraphicUnit.year' => '2024'],
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
            'query' => ['stratigraphicUnit.number[lt]' => '500'],
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
            'query' => ['stratigraphicUnit.interpretation' => 'false'],
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
            'query' => ['exists[stratigraphicUnit.description]' => 'false'],
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
                if (!$su['stratigraphicUnit']['interpretation']) {
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
}
