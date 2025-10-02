<?php

namespace App\Tests\Functional\Api\Resource;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Tests\Functional\Api\ApiTestProviderTrait;
use App\Tests\Functional\ApiTestRequestTrait;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class ApiResourceContextStratigraphicUnitTest extends ApiTestCase
{
    use ApiTestRequestTrait;
    use ApiTestProviderTrait;

    protected function setUp(): void
    {
        parent::setUp();
        static::$alwaysBootKernel = false;
        $this->parameterBag = self::getContainer()->get(ParameterBagInterface::class);
    }

    protected function tearDown(): void
    {
        $this->parameterBag = null;
        parent::tearDown();
    }

    public function testGetCollectionReturnsContextStratigraphicUnits(): void
    {
        $client = self::createClient();

        $response = $this->apiRequest($client, 'GET', '/api/data/context_stratigraphic_units');
        $this->assertSame(200, $response->getStatusCode());

        $data = $response->toArray();
        $this->assertIsArray($data['member']);
        // Should have fixture data available
        $this->assertNotEmpty($data['member']);

        $firstItem = $data['member'][0];
        $this->assertArrayHasKey('id', $firstItem);
        $this->assertArrayHasKey('context', $firstItem);
        $this->assertArrayHasKey('stratigraphicUnit', $firstItem);
    }

    public function testPostCreatesContextStratigraphicUnit(): void
    {
        $client = self::createClient();
        $token = $this->getUserToken($client, 'user_admin');

        // Use fixture data instead of creating new items
        $context = $this->getFixtureContextByName('floor 1');
        $stratigraphicUnit = $this->getFixtureStratigraphicUnit('ME', 104);

        $this->assertNotNull($context, 'Fixture context should exist');
        $this->assertNotNull($stratigraphicUnit, 'Fixture stratigraphic unit should exist');

        $payload = [
            'context' => $context['@id'],
            'stratigraphicUnit' => $stratigraphicUnit['@id'],
        ];

        $response = $this->apiRequest($client, 'POST', '/api/data/context_stratigraphic_units', [
            'token' => $token,
            'json' => $payload,
        ]);

        $this->assertSame(201, $response->getStatusCode());
        $createdData = $response->toArray();
        $this->assertEquals($payload['context'], $createdData['context']['@id']);
        $this->assertEquals($payload['stratigraphicUnit'], $createdData['stratigraphicUnit']['@id']);
    }

    public function testPostValidationFailsWithMissingStratigraphicUnit(): void
    {
        $client = self::createClient();
        $token = $this->getUserToken($client, 'user_admin');

        $contexts = $this->getFixtureContexts();

        $payload = [
            'context' => $contexts[0]['@id'],
        ];

        $response = $this->apiRequest($client, 'POST', '/api/data/context_stratigraphic_units', [
            'token' => $token,
            'json' => $payload,
        ]);

        $this->assertSame(422, $response->getStatusCode());
        $data = $response->toArray(false);
        $this->assertArrayHasKey('violations', $data);

        $contextViolation = array_filter($data['violations'], fn ($violation) => 'stratigraphicUnit' === $violation['propertyPath']);
        $this->assertNotEmpty($contextViolation);
    }

    public function testPostValidationFailsWithMissingContext(): void
    {
        $client = self::createClient();
        $token = $this->getUserToken($client, 'user_admin');

        $stratigraphicUnit = $this->getFixtureStratigraphicUnit('ME', 101);

        $payload = [
            'stratigraphicUnit' => $stratigraphicUnit['@id'],
        ];

        $response = $this->apiRequest($client, 'POST', '/api/data/context_stratigraphic_units', [
            'token' => $token,
            'json' => $payload,
        ]);

        $this->assertSame(422, $response->getStatusCode());
        $data = $response->toArray(false);
        $this->assertArrayHasKey('violations', $data);

        $contextViolation = array_filter($data['violations'], fn ($violation) => 'context' === $violation['propertyPath']);
        $this->assertNotEmpty($contextViolation);
    }

    public function testPostValidationFailsIfRelatedEntitiesBelongToDifferentSites(): void
    {
        $client = self::createClient();
        $token = $this->getUserToken($client, 'user_admin');

        $contexts = $this->getFixtureContexts();
        $stratigraphicUnits = $this->getFixtureStratigraphicUnits();

        // Use fixture data instead of creating new items
        $context = $contexts[0];

        // Find the first stratigraphic unit that has a different site than the context
        $stratigraphicUnit = null;
        foreach ($stratigraphicUnits as $su) {
            if ($su['site']['@id'] !== $context['site']['@id']) {
                $stratigraphicUnit = $su;
                break;
            }
        }

        $this->assertNotNull($context, 'Fixture context should exist');
        $this->assertNotNull($stratigraphicUnit, 'Fixture stratigraphic unit should exist');
        $this->assertNotEquals($context['site']['@id'], $stratigraphicUnit['site']['@id']);

        $payload = [
            'context' => $context['@id'],
            'stratigraphicUnit' => $stratigraphicUnit['@id'],
        ];

        $response = $this->apiRequest($client, 'POST', '/api/data/context_stratigraphic_units', [
            'token' => $token,
            'json' => $payload,
        ]);

        $this->assertSame(422, $response->getStatusCode());
        $data = $response->toArray(false);
        $this->assertArrayHasKey('violations', $data);

        $contextViolation = array_filter($data['violations'], fn ($violation) => str_contains(strtolower($violation['message']), 'same site'));
        $this->assertNotEmpty($contextViolation);
    }

    public function testPostUniqueConstraintViolation(): void
    {
        $client = self::createClient();
        $token = $this->getUserToken($client, 'user_admin');

        $contextSus = $this->getFixtureContextStratigraphicUnits();

        $payload = [
            'context' => $contextSus[0]['context']['@id'],
            'stratigraphicUnit' => $contextSus[0]['stratigraphicUnit']['@id'],
        ];

        // Try to create the same relationship again - should fail with validation error
        $response = $this->apiRequest($client, 'POST', '/api/data/context_stratigraphic_units', [
            'token' => $token,
            'json' => $payload,
        ]);

        // Should return 422 validation error, not 500 database error
        $this->assertSame(422, $response->getStatusCode());
        $data = $response->toArray(false);
        $this->assertArrayHasKey('violations', $data);
        $this->assertGreaterThan(0, count($data['violations']));

        // Check that the violation is about uniqueness
        $violations = $data['violations'];
        $uniqueViolation = array_filter($violations, function ($violation) {
            return str_contains(strtolower($violation['message']), 'duplicate');
        });
        $this->assertNotEmpty($uniqueViolation, 'Should have a uniqueness violation');
    }
}
