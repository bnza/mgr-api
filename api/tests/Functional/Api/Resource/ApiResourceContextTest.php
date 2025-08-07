<?php

namespace App\Tests\Functional\Api\Resource;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Symfony\Bundle\Test\Client;
use App\Tests\Functional\Api\ApiTestProviderTrait;
use App\Tests\Functional\ApiTestRequestTrait;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class ApiResourceContextTest extends ApiTestCase
{
    use ApiTestRequestTrait;
    use ApiTestProviderTrait;

    private ?ParameterBagInterface $parameterBag = null;

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

    public function testGetCollectionReturnsContexts(): void
    {
        $client = self::createClient();

        $response = $this->apiRequest($client, 'GET', '/api/data/contexts');
        $this->assertSame(200, $response->getStatusCode());
        $data = $response->toArray();
        $this->assertIsArray($data['member']);
        $this->assertNotEmpty($data['member']);

        // Check structure of first item
        $firstItem = $data['member'][0];
        $this->assertArrayHasKey('id', $firstItem);
        $this->assertArrayHasKey('site', $firstItem);
        $this->assertArrayHasKey('type', $firstItem);
        $this->assertArrayHasKey('name', $firstItem);
    }

    public function testPostCreatesContext(): void
    {
        $client = self::createClient();
        $token = $this->getUserToken($client, 'user_admin');

        $site = $this->getFixtureSites()[0];
        $type = $this->getFixtureContextTypes()[0];

        // Prepare payload with valid data
        $payload = [
            'site' => $site['@id'],
            'type' => $type['@id'],
            'name' => 'Test context '.uniqid(),
            'description' => 'Test description',
        ];

        $response = $this->apiRequest($client, 'POST', '/api/data/contexts', [
            'token' => $token,
            'json' => $payload,
        ]);

        $this->assertSame(201, $response->getStatusCode());
        $createdData = $response->toArray();
        $this->assertArrayHasKey('id', $createdData);
        $this->assertEquals($payload['site'], $createdData['site']['@id']);
        $this->assertEquals($payload['type'], $createdData['type']['@id']);
        $this->assertEquals($payload['name'], $createdData['name']);
    }

    public function testGetItemReturnsContext(): void
    {
        $client = self::createClient();

        $newContext = $this->createContext($client, 'user_admin');
        $createdId = $newContext['id'];

        $response = $this->apiRequest($client, 'GET', "/api/data/contexts/$createdId");
        $this->assertSame(200, $response->getStatusCode());
        $data = $response->toArray();
        $this->assertEquals($createdId, $data['id']);
        foreach (['name', 'description'] as $field) {
            $this->assertEquals($newContext[$field], $data[$field]);
        }
    }

    public function testPatchUpdatesContext(): void
    {
        $client = self::createClient();

        $newContext = $this->createContext($client, 'user_admin');
        $createdId = $newContext['id'];
        $newName = 'Updated context name '.uniqid();

        $token = $this->getUserToken($client, 'user_admin');

        // PATCH
        $responsePatch = $this->apiRequest($client, 'PATCH', "/api/data/contexts/$createdId", [
            'token' => $token,
            'json' => ['name' => $newName],
        ]);
        $this->assertSame(200, $responsePatch->getStatusCode());
        $patchedData = $responsePatch->toArray();
        $this->assertEquals($newName, $patchedData['name']);
    }

    public function testDeleteRemovesContext(): void
    {
        $client = self::createClient();
        $newContext = $this->createContext($client, 'user_admin');

        $createdId = $newContext['id'];
        $token = $this->getUserToken($client, 'user_admin');

        // DELETE
        $responseDelete = $this->apiRequest($client, 'DELETE', "/api/data/contexts/$createdId", [
            'token' => $token,
        ]);
        $this->assertSame(204, $responseDelete->getStatusCode());

        // Confirm deletion
        $responseGet = $this->apiRequest($client, 'GET', "/api/data/contexts/$createdId");
        $this->assertSame(404, $responseGet->getStatusCode());
    }

    public function testPostValidationFailsWithMissingSite(): void
    {
        $client = self::createClient();
        $token = $this->getUserToken($client, 'user_admin');

        $type = $this->getFixtureContextTypes()[0];

        $payload = [
            'type' => $type['@id'],
            'name' => 'Test context',
            'description' => 'Test description',
        ];

        $response = $this->apiRequest($client, 'POST', '/api/data/contexts', [
            'token' => $token,
            'json' => $payload,
        ]);

        $this->assertSame(422, $response->getStatusCode());
        $data = $response->toArray(false);
        $this->assertArrayHasKey('violations', $data);
        $this->assertGreaterThan(0, count($data['violations']));

        // Check that site validation failed
        $siteViolation = array_filter($data['violations'], fn ($violation) => 'site' === $violation['propertyPath']);
        $this->assertNotEmpty($siteViolation);
    }

    public function testPostValidationFailsWithMissingType(): void
    {
        $client = self::createClient();
        $token = $this->getUserToken($client, 'user_admin');
        $site = $this->getFixtureSites()[0];

        $payload = [
            'site' => $site['@id'],
            'name' => 'Test context',
            'description' => 'Test description',
        ];

        $response = $this->apiRequest($client, 'POST', '/api/data/contexts', [
            'token' => $token,
            'json' => $payload,
        ]);

        $this->assertSame(422, $response->getStatusCode());
        $data = $response->toArray(false);
        $this->assertArrayHasKey('violations', $data);
        $this->assertGreaterThan(0, count($data['violations']));

        // Check that type validation failed
        $typeViolation = array_filter($data['violations'], fn ($violation) => 'type' === $violation['propertyPath']);
        $this->assertNotEmpty($typeViolation);
    }

    public function testPostValidationFailsWithMissingName(): void
    {
        $client = self::createClient();
        $token = $this->getUserToken($client, 'user_admin');
        $site = $this->getFixtureSites()[0];
        $type = $this->getFixtureContextTypes()[0];

        $payload = [
            'site' => $site['@id'],
            'type' => $type['@id'],
            'description' => 'Test description',
        ];

        $response = $this->apiRequest($client, 'POST', '/api/data/contexts', [
            'token' => $token,
            'json' => $payload,
        ]);

        $this->assertSame(422, $response->getStatusCode());
        $data = $response->toArray(false);
        $this->assertArrayHasKey('violations', $data);
        $this->assertGreaterThan(0, count($data['violations']));

        // Check that name validation failed
        $nameViolation = array_filter($data['violations'], fn ($violation) => 'name' === $violation['propertyPath']);
        $this->assertNotEmpty($nameViolation);
    }

    private function createContext(Client $client, string $username = 'user_admin', array $payload = [], bool $test = true): array
    {
        $token = $this->getUserToken($client, $username);
        $originalPayload = [...$payload];

        if (!array_key_exists('site', $payload)) {
            $payload['site'] = $this->getFixtureSites()[0]['@id'];
        }
        if (!array_key_exists('type', $payload)) {
            $payload['type'] = $this->getFixtureContextTypes()[0]['@id'];
        }
        if (!array_key_exists('name', $payload)) {
            $payload['name'] = 'Test context '.uniqid();
        }
        if (!array_key_exists('description', $payload)) {
            $payload['description'] = 'Test description '.uniqid();
        }

        $response = $this->apiRequest($client, 'POST', '/api/data/contexts', [
            'token' => $token,
            'json' => $payload,
        ]);
        $this->assertSame(201, $response->getStatusCode());
        $createdData = $response->toArray();

        if ($test) {
            $this->assertArrayHasKey('id', $createdData);
            if (array_key_exists('site', $originalPayload)) {
                $this->assertEquals($originalPayload['site'], $createdData['site']['@id']);
            }
            if (array_key_exists('type', $originalPayload)) {
                $this->assertEquals($originalPayload['type'], $createdData['type']['@id']);
            }
            foreach (['name', 'description'] as $field) {
                if (array_key_exists($field, $originalPayload)) {
                    $this->assertEquals($originalPayload[$field], $createdData[$field]);
                }
            }
        }

        return $createdData;
    }

    /**
     * Get fixture context types without creating new ones.
     */
    protected function getFixtureContextTypes(array $queryParams = []): array
    {
        $client = self::createClient();
        $url = '/api/vocabulary/context/types';
        if (!empty($queryParams)) {
            $url .= '?'.http_build_query($queryParams);
        }
        $response = $this->apiRequest($client, 'GET', $url);
        $this->assertSame(200, $response->getStatusCode());

        return $response->toArray()['member'];
    }
}
