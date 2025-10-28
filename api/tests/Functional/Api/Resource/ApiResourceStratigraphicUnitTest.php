<?php

namespace App\Tests\Functional\Api\Resource;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Symfony\Bundle\Test\Client;
use App\Tests\Functional\Api\ApiTestProviderTrait;
use App\Tests\Functional\ApiTestRequestTrait;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class ApiResourceStratigraphicUnitTest extends ApiTestCase
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

    public function testGetCollectionReturnsStratigraphicUnits(): void
    {
        $client = self::createClient();

        $response = $this->apiRequest($client, 'GET', '/api/data/stratigraphic_units');
        $this->assertSame(200, $response->getStatusCode());
        $data = $response->toArray();
        $this->assertIsArray($data['member']);
        $this->assertNotEmpty($data['member']);
        // Check structure of first item
        $firstItem = $data['member'][0];
        $this->assertArrayHasKey('id', $firstItem);
        $this->assertArrayHasKey('site', $firstItem);
        $this->assertArrayHasKey('year', $firstItem);
        $this->assertArrayHasKey('number', $firstItem);
    }

    public function testPostCreatesStratigraphicUnit(): void
    {
        $client = self::createClient();
        $token = $this->getUserToken($client, 'user_admin'); // Assuming an admin user

        $site = $this->getSites()[0];

        // Prepare payload, replace placeholders with valid data
        $payload = [
            'site' => $site['@id'], // Example site ID
            'year' => 2023,
            'number' => 5,
            'description' => 'Test description',
            'interpretation' => 'Test interpretation',
        ];

        $response = $this->apiRequest($client, 'POST', '/api/data/stratigraphic_units', [
            'token' => $token,
            'json' => $payload,
        ]);
        $this->assertSame(201, $response->getStatusCode());
        $createdData = $response->toArray();
        $this->assertArrayHasKey('id', $createdData);
        $this->assertEquals($payload['site'], $createdData['site']['@id']);
    }

    public function testGetItemReturnsStratigraphicUnit(): void
    {
        $client = self::createClient();

        $newStratigraphicUnit = $this->createStratigraphicUnit($client, 'user_admin');

        $createdId = $newStratigraphicUnit['id'];

        $response = $this->apiRequest($client, 'GET', "/api/data/stratigraphic_units/$createdId");
        $this->assertSame(200, $response->getStatusCode());
        $data = $response->toArray();
        $this->assertEquals($createdId, $data['id']);
        foreach (['year', 'number', 'description', 'interpretation'] as $field) {
            $this->assertEquals($newStratigraphicUnit[$field], $data[$field]);
        }
    }

    public function testPatchUpdatesStratigraphicUnit(): void
    {
        $client = self::createClient();

        $newStratigraphicUnit = $this->createStratigraphicUnit($client, 'user_admin');

        $createdId = $newStratigraphicUnit['id'];
        $newYear = $newStratigraphicUnit['year'] - 1;

        $token = $this->getUserToken($client, 'user_admin');
        // PATCH
        $responsePatch = $this->apiRequest($client, 'PATCH', "/api/data/stratigraphic_units/$createdId", [
            'token' => $token,
            'json' => ['year' => $newYear],
        ]);
        $this->assertSame(200, $responsePatch->getStatusCode());
        $patchedData = $responsePatch->toArray();
        $this->assertEquals($newYear, $patchedData['year']);
    }

    public function testDeleteRemovesStratigraphicUnit(): void
    {
        $client = self::createClient();
        $newStratigraphicUnit = $this->createStratigraphicUnit($client, 'user_admin');

        $createdId = $newStratigraphicUnit['id'];
        $token = $this->getUserToken($client, 'user_admin');

        // DELETE
        $responseDelete = $this->apiRequest($client, 'DELETE', "/api/data/stratigraphic_units/$createdId", [
            'token' => $token,
        ]);
        $this->assertSame(204, $responseDelete->getStatusCode());

        // Confirm deletion
        $responseGet = $this->apiRequest($client, 'GET', "/api/data/stratigraphic_units/$createdId");
        $this->assertSame(404, $responseGet->getStatusCode());
    }

    public function testPostValidationFailsWithMissingSite(): void
    {
        $client = self::createClient();
        $token = $this->getUserToken($client, 'user_admin');

        $payload = [
            'year' => 2023,
            'number' => 5,
            'description' => 'Test description',
            'interpretation' => 'Test interpretation',
        ];

        $response = $this->apiRequest($client, 'POST', '/api/data/stratigraphic_units', [
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

    public function testPostValidationFailsWithMissingNumber(): void
    {
        $client = self::createClient();
        $token = $this->getUserToken($client, 'user_admin');
        $site = $this->getSites()[0];

        $payload = [
            'site' => $site['@id'],
            'year' => 2023,
            'description' => 'Test description',
            'interpretation' => 'Test interpretation',
        ];

        $response = $this->apiRequest($client, 'POST', '/api/data/stratigraphic_units', [
            'token' => $token,
            'json' => $payload,
        ]);

        $this->assertSame(422, $response->getStatusCode());
        $data = $response->toArray(false);
        $this->assertArrayHasKey('violations', $data);
        $this->assertGreaterThan(0, count($data['violations']));

        // Check that number validation failed
        $numberViolation = array_filter($data['violations'], fn ($violation) => 'number' === $violation['propertyPath']);
        $this->assertNotEmpty($numberViolation);
    }

    public function testPostValidationFailsWithNegativeNumber(): void
    {
        $client = self::createClient();
        $token = $this->getUserToken($client, 'user_admin');
        $site = $this->getSites()[0];

        $payload = [
            'site' => $site['@id'],
            'year' => 2023,
            'number' => -5, // Negative number should fail validation
            'description' => 'Test description',
            'interpretation' => 'Test interpretation',
        ];

        $response = $this->apiRequest($client, 'POST', '/api/data/stratigraphic_units', [
            'token' => $token,
            'json' => $payload,
        ]);

        $this->assertSame(422, $response->getStatusCode());
        $data = $response->toArray(false);
        $this->assertArrayHasKey('violations', $data);
        $this->assertGreaterThan(0, count($data['violations']));

        // Check that number validation failed due to positive constraint
        $numberViolation = array_filter($data['violations'], fn ($violation) => 'number' === $violation['propertyPath']);
        $this->assertNotEmpty($numberViolation);
    }

    public function testPostValidationFailsWithZeroNumber(): void
    {
        $client = self::createClient();
        $token = $this->getUserToken($client, 'user_admin');
        $site = $this->getSites()[0];

        $payload = [
            'site' => $site['@id'],
            'year' => 2023,
            'number' => 0, // Zero should fail positive validation
            'description' => 'Test description',
            'interpretation' => 'Test interpretation',
        ];

        $response = $this->apiRequest($client, 'POST', '/api/data/stratigraphic_units', [
            'token' => $token,
            'json' => $payload,
        ]);

        $this->assertSame(422, $response->getStatusCode());
        $data = $response->toArray(false);
        $this->assertArrayHasKey('violations', $data);
        $this->assertGreaterThan(0, count($data['violations']));

        // Check that number validation failed due to positive constraint
        $numberViolation = array_filter($data['violations'], fn ($violation) => 'number' === $violation['propertyPath']);
        $this->assertNotEmpty($numberViolation);
    }

    public function testPostValidationSucceedsWithValidData(): void
    {
        $client = self::createClient();
        $token = $this->getUserToken($client, 'user_admin');
        $site = $this->getSites()[0];

        $payload = [
            'site' => $site['@id'],
            'year' => 2023,
            'number' => 1, // Positive number should pass
            'description' => 'Test description',
            'interpretation' => 'Test interpretation',
        ];

        $response = $this->apiRequest($client, 'POST', '/api/data/stratigraphic_units', [
            'token' => $token,
            'json' => $payload,
        ]);

        $this->assertSame(201, $response->getStatusCode());
        $data = $response->toArray();
        $this->assertArrayHasKey('id', $data);
        $this->assertEquals($payload['site'], $data['site']['@id']);
        $this->assertEquals($payload['number'], $data['number']);
    }

    public function testUniqueConstraintViolation(): void
    {
        $client = self::createClient();
        $token = $this->getUserToken($client, 'user_admin');
        $site = $this->getSites()[0];

        // Create first stratigraphic unit
        $payload = [
            'site' => $site['@id'],
            'year' => 2023,
            'number' => 99,
            'description' => 'First unit',
            'interpretation' => 'First interpretation',
        ];

        $response1 = $this->apiRequest($client, 'POST', '/api/data/stratigraphic_units', [
            'token' => $token,
            'json' => $payload,
        ]);
        $this->assertSame(201, $response1->getStatusCode());

        // Try to create second unit with same site, year, and number
        $payload2 = [
            'site' => $site['@id'],
            'year' => 2023,
            'number' => 99, // Same combination should fail
            'description' => 'Second unit',
            'interpretation' => 'Second interpretation',
        ];

        $response2 = $this->apiRequest($client, 'POST', '/api/data/stratigraphic_units', [
            'token' => $token,
            'json' => $payload2,
        ]);

        // Should fail due to unique constraint
        $this->assertSame(422, $response2->getStatusCode());
        $data = $response2->toArray(false);
        $this->assertArrayHasKey('violations', $data);
        $this->assertGreaterThan(0, count($data['violations']));

        // Check that number validation failed due to positive constraint
        $numberViolation = array_filter($data['violations'], fn ($violation) => 'site' === $violation['propertyPath']);
        $this->assertNotEmpty($numberViolation);
    }

    public function testUniqueConstraintViolationWithEmptyYear(): void
    {
        $client = self::createClient();
        $token = $this->getUserToken($client, 'user_admin');
        $site = $this->getSites()[0];

        // Create first stratigraphic unit
        $payload = [
            'site' => $site['@id'],
            'number' => 99,
            'description' => 'First unit',
            'interpretation' => 'First interpretation',
        ];

        $response1 = $this->apiRequest($client, 'POST', '/api/data/stratigraphic_units', [
            'token' => $token,
            'json' => $payload,
        ]);
        $this->assertSame(201, $response1->getStatusCode());

        // Try to create second unit with same site, year, and number
        $payload2 = [
            'site' => $site['@id'],
            'number' => 99, // Same combination should fail
            'description' => 'Second unit',
            'interpretation' => 'Second interpretation',
        ];

        $response2 = $this->apiRequest($client, 'POST', '/api/data/stratigraphic_units', [
            'token' => $token,
            'json' => $payload2,
        ]);

        // Should fail due to unique constraint
        $this->assertSame(422, $response2->getStatusCode());
        $data = $response2->toArray(false);
        $this->assertArrayHasKey('violations', $data);
        $this->assertGreaterThan(0, count($data['violations']));

        // Check that number validation failed due to positive constraint
        $numberViolation = array_filter($data['violations'], fn ($violation) => 'site' === $violation['propertyPath']);
        $this->assertNotEmpty($numberViolation);
    }

    public function testDeleteStratigraphicUnitIsBlockedWhenReferencedByOtherEntities(): void
    {
        $client = self::createClient();
        $adminToken = $this->getUserToken($client, 'user_admin');

        // Build a map of stratigraphic unit IRI => [set of short class names that reference that SU]
        $referencedBy = [];

        // Helper to add mapping
        $addRef = static function (array &$map, ?string $suIri, string $shortClass): void {
            if (!$suIri) {
                return;
            }
            if (!isset($map[$suIri])) {
                $map[$suIri] = [];
            }
            if (!in_array($shortClass, $map[$suIri], true)) {
                $map[$suIri][] = $shortClass;
            }
        };

        // Collect microstratigraphic units referencing stratigraphic units
        $musResponse = $this->apiRequest($client, 'GET', '/api/data/microstratigraphic_units', [
            'token' => $adminToken,
        ]);
        if (200 === $musResponse->getStatusCode()) {
            $mus = $musResponse->toArray();
            foreach ($mus['member'] ?? [] as $mu) {
                $suIri = $mu['stratigraphicUnit']['@id'] ?? null;
                $addRef($referencedBy, $suIri, 'MicrostratigraphicUnit');
            }
        }

        // Collect potteries referencing stratigraphic units
        $potteriesResponse = $this->apiRequest($client, 'GET', '/api/data/potteries', [
            'token' => $adminToken,
        ]);
        if (200 === $potteriesResponse->getStatusCode()) {
            $potteries = $potteriesResponse->toArray();
            foreach ($potteries['member'] ?? [] as $pottery) {
                $suIri = $pottery['stratigraphicUnit']['@id'] ?? null;
                $addRef($referencedBy, $suIri, 'Pottery');
            }
        }

        // Collect individuals referencing stratigraphic units
        $individualsResponse = $this->apiRequest($client, 'GET', '/api/data/individuals', [
            'token' => $adminToken,
        ]);
        if (200 === $individualsResponse->getStatusCode()) {
            $individuals = $individualsResponse->toArray();
            foreach ($individuals['member'] ?? [] as $individual) {
                $suIri = $individual['stratigraphicUnit']['@id'] ?? null;
                $addRef($referencedBy, $suIri, 'Individual');
            }
        }

        // Collect zoo bones referencing stratigraphic units
        $bonesResponse = $this->apiRequest($client, 'GET', '/api/data/zoo/bones', [
            'token' => $adminToken,
        ]);
        if (200 === $bonesResponse->getStatusCode()) {
            $bones = $bonesResponse->toArray();
            foreach ($bones['member'] ?? [] as $bone) {
                $suIri = $bone['stratigraphicUnit']['@id'] ?? null;
                $addRef($referencedBy, $suIri, 'Bone');
            }
        }

        // Collect zoo teeth referencing stratigraphic units
        $teethResponse = $this->apiRequest($client, 'GET', '/api/data/zoo/teeth', [
            'token' => $adminToken,
        ]);
        if (200 === $teethResponse->getStatusCode()) {
            $teeth = $teethResponse->toArray();
            foreach ($teeth['member'] ?? [] as $tooth) {
                $suIri = $tooth['stratigraphicUnit']['@id'] ?? null;
                $addRef($referencedBy, $suIri, 'Tooth');
            }
        }

        // Collect botany seeds referencing stratigraphic units
        $seedsResponse = $this->apiRequest($client, 'GET', '/api/data/botany/seeds', [
            'token' => $adminToken,
        ]);
        if (200 === $seedsResponse->getStatusCode()) {
            $seeds = $seedsResponse->toArray();
            foreach ($seeds['member'] ?? [] as $seed) {
                $suIri = $seed['stratigraphicUnit']['@id'] ?? null;
                $addRef($referencedBy, $suIri, 'Seed');
            }
        }

        // Collect botany charcoals referencing stratigraphic units
        $charcoalsResponse = $this->apiRequest($client, 'GET', '/api/data/botany/charcoals', [
            'token' => $adminToken,
        ]);
        if (200 === $charcoalsResponse->getStatusCode()) {
            $charcoals = $charcoalsResponse->toArray();
            foreach ($charcoals['member'] ?? [] as $charcoal) {
                $suIri = $charcoal['stratigraphicUnit']['@id'] ?? null;
                $addRef($referencedBy, $suIri, 'Charcoal');
            }
        }

        // Pick a stratigraphic unit that is referenced by at least one entity type
        $targetSuIri = null;
        $expectedClasses = [];
        foreach ($referencedBy as $suIri => $classes) {
            if (!empty($classes)) {
                $targetSuIri = $suIri;
                $expectedClasses = $classes;
                break;
            }
        }

        if (!$targetSuIri) {
            $this->markTestSkipped('No referenced stratigraphic unit found in fixtures to test delete validator.');
        }

        // Try to delete the referenced stratigraphic unit as admin
        $deleteResponse = $this->apiRequest($client, 'DELETE', $targetSuIri, [
            'token' => $adminToken,
        ]);

        $this->assertSame(422, $deleteResponse->getStatusCode(), 'Deleting a referenced stratigraphic unit should return 422 Unprocessable Entity');

        $payload = $deleteResponse->toArray(false);
        $this->assertArrayHasKey('violations', $payload, 'Validation response should contain violations');
        $violations = $payload['violations'];
        $this->assertGreaterThan(0, count($violations), 'There should be at least one violation');

        // Combine violation messages to look for our expected class names
        $messages = array_map(static fn ($v) => $v['message'] ?? '', $violations);
        $fullMessageBlob = implode(" \n ", $messages);

        // Ensure each expected class short name is mentioned in the error message
        foreach ($expectedClasses as $shortClass) {
            $this->assertStringContainsString($shortClass, $fullMessageBlob, sprintf('Violation message should mention %s', $shortClass));
        }
    }

    private function createStratigraphicUnit(Client $client, string $username = 'user_admin', array $payload = [], bool $test = true): array
    {
        $token = $this->getUserToken($client, $username);
        $originalPayload = [...$payload];
        if (!array_key_exists('site', $payload)) {
            $payload['site'] = $this->getSites()[0]['@id'];
        }
        if (!array_key_exists('year', $payload)) {
            $payload['year'] = rand(2000, 2025);
        }
        if (!array_key_exists('number', $payload)) {
            $payload['number'] = rand(1, 200);
        }
        if (!array_key_exists('description', $payload)) {
            $payload['description'] = 'Test description '.uniqid();
        }
        if (!array_key_exists('interpretation', $payload)) {
            $payload['interpretation'] = 'Test interpretation '.uniqid();
        }

        $response = $this->apiRequest($client, 'POST', '/api/data/stratigraphic_units', [
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
            foreach (['year', 'number', 'description', 'interpretation'] as $field) {
                if (array_key_exists($field, $originalPayload)) {
                    $this->assertEquals($originalPayload[$field], $createdData[$field]);
                }
            }
        }

        return $createdData;
    }
}
