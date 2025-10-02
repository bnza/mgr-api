<?php

namespace App\Tests\Functional\Api\Resource;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Tests\Functional\ApiTestRequestTrait;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class ApiResourceStratigraphicUnitRelationshipTest extends ApiTestCase
{
    use ApiTestRequestTrait;

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

    public function testCreateStratigraphicUnitRelationshipRequiredFieldsValidation(): void
    {
        $client = self::createClient();
        $token = $this->getUserToken($client, 'user_admin');

        // Get valid vocabulary and stratigraphic units
        $relations = $this->getVocabulary(['stratigraphic_unit', 'relationships']);
        $stratigraphicUnits = $this->getSiteStratigraphicUnits();

        if (empty($relations)) {
            $this->markTestSkipped('No stratigraphic unit relations available for this test');
        }

        if (count($stratigraphicUnits) < 2) {
            $this->markTestSkipped('Need at least 2 stratigraphic units for this test');
        }

        $relationIri = $relations[0]['@id'];
        $firstSuIri = $stratigraphicUnits[0]['@id'];
        $secondSuIri = $stratigraphicUnits[1]['@id'];

        // Test missing lftStratigraphicUnit
        $response = $this->apiRequest($client, 'POST', '/api/data/stratigraphic_unit_relationships', [
            'token' => $token,
            'json' => [
                'relationship' => $relationIri,
                'rgtStratigraphicUnit' => $firstSuIri,
            ],
        ]);

        $this->assertSame(422, $response->getStatusCode());
        $data = $response->toArray(false);
        $this->assertArrayHasKey('violations', $data);
        $this->assertGreaterThan(0, count($data['violations']));

        $lftSuViolation = array_filter($data['violations'], fn ($violation) => 'lftStratigraphicUnit' === $violation['propertyPath']);
        $this->assertNotEmpty($lftSuViolation);

        // Test missing relationship
        $response = $this->apiRequest($client, 'POST', '/api/data/stratigraphic_unit_relationships', [
            'token' => $token,
            'json' => [
                'lftStratigraphicUnit' => $firstSuIri,
                'rgtStratigraphicUnit' => $secondSuIri,
            ],
        ]);

        $this->assertSame(422, $response->getStatusCode());
        $data = $response->toArray(false);
        $this->assertArrayHasKey('violations', $data);
        $this->assertGreaterThan(0, count($data['violations']));

        $relationshipViolation = array_filter($data['violations'], fn ($violation) => 'relationship' === $violation['propertyPath']);
        $this->assertNotEmpty($relationshipViolation);

        // Test missing rgtStratigraphicUnit
        $response = $this->apiRequest($client, 'POST', '/api/data/stratigraphic_unit_relationships', [
            'token' => $token,
            'json' => [
                'lftStratigraphicUnit' => $firstSuIri,
                'relationship' => $relationIri,
            ],
        ]);

        $this->assertSame(422, $response->getStatusCode());
        $data = $response->toArray(false);
        $this->assertArrayHasKey('violations', $data);
        $this->assertGreaterThan(0, count($data['violations']));

        $rgtSuViolation = array_filter($data['violations'], fn ($violation) => 'rgtStratigraphicUnit' === $violation['propertyPath']);
        $this->assertNotEmpty($rgtSuViolation);
    }

    public function testCreateStratigraphicUnitRelationshipSelfReferencingValidation(): void
    {
        $client = self::createClient();
        $token = $this->getUserToken($client, 'user_admin');

        // Get valid vocabulary and stratigraphic units
        $relations = $this->getVocabulary(['stratigraphic_unit', 'relationships']);
        $stratigraphicUnits = $this->getSiteStratigraphicUnits();

        if (empty($relations)) {
            $this->markTestSkipped('No stratigraphic unit relations available for this test');
        }

        if (empty($stratigraphicUnits)) {
            $this->markTestSkipped('No stratigraphic units available for this test');
        }

        $relationIri = $relations[0]['@id'];
        $suIri = $stratigraphicUnits[0]['@id'];

        // Test self-referencing relationship (rgtStratigraphicUnit equals lftStratigraphicUnit)
        $response = $this->apiRequest($client, 'POST', '/api/data/stratigraphic_unit_relationships', [
            'token' => $token,
            'json' => [
                'lftStratigraphicUnit' => $suIri,
                'relationship' => $relationIri,
                'rgtStratigraphicUnit' => $suIri,
            ],
        ]);

        $this->assertSame(422, $response->getStatusCode());
        $data = $response->toArray(false);
        $this->assertArrayHasKey('violations', $data);
        $this->assertGreaterThan(0, count($data['violations']));

        $selfReferencingViolation = array_filter($data['violations'], fn ($violation) => 'rgtStratigraphicUnit' === $violation['propertyPath']);
        $this->assertNotEmpty($selfReferencingViolation);

        // Check that the violation message contains the expected text
        $violation = array_values($selfReferencingViolation)[0];
        $this->assertStringContainsString('Self referencing relationship is not allowed.', $violation['message']);
    }

    public function testCreateStratigraphicUnitRelationshipBelongToSameSiteValidation(): void
    {
        $client = self::createClient();
        $token = $this->getUserToken($client, 'user_admin');

        // Get valid vocabulary and stratigraphic units
        $relations = $this->getVocabulary(['stratigraphic_unit', 'relationships']);
        $stratigraphicUnits = $this->getSiteStratigraphicUnits();

        if (empty($relations)) {
            $this->markTestSkipped('No stratigraphic unit relations available for this test');
        }

        if (count($stratigraphicUnits) < 2) {
            $this->markTestSkipped('Need at least 2 stratigraphic units from different sites for this test');
        }

        $relationIri = $relations[0]['@id'];

        // Find stratigraphic units from different sites
        $lftSu = null;
        $rgtSu = null;

        foreach ($stratigraphicUnits as $su1) {
            foreach ($stratigraphicUnits as $su2) {
                if ($su1['site']['id'] !== $su2['site']['id']) {
                    $lftSu = $su1;
                    $rgtSu = $su2;
                    break 2;
                }
            }
        }

        if (!$lftSu || !$rgtSu) {
            $this->markTestSkipped('Could not find stratigraphic units from different sites for this test');
        }

        // Test creating relationship with stratigraphic units from different sites
        $response = $this->apiRequest($client, 'POST', '/api/data/stratigraphic_unit_relationships', [
            'token' => $token,
            'json' => [
                'lftStratigraphicUnit' => $lftSu['@id'],
                'relationship' => $relationIri,
                'rgtStratigraphicUnit' => $rgtSu['@id'],
            ],
        ]);

        $this->assertSame(422, $response->getStatusCode());
        $data = $response->toArray(false);
        $this->assertArrayHasKey('violations', $data);
        $this->assertGreaterThan(0, count($data['violations']));

        // The BelongToTheSameSite validator should trigger a violation
        $sameSiteViolation = array_filter($data['violations'], fn ($violation) => str_contains($violation['message'], 'same site'));
        $this->assertNotEmpty($sameSiteViolation);
    }

    public function testCreateStratigraphicUnitRelationshipSuccess(): void
    {
        $client = self::createClient();
        $token = $this->getUserToken($client, 'user_admin');

        // Get valid vocabulary and stratigraphic units
        $relations = $this->getVocabulary(['stratigraphic_unit', 'relationships']);
        $stratigraphicUnits = $this->getSiteStratigraphicUnits();

        if (empty($relations)) {
            $this->markTestSkipped('No stratigraphic unit relations available for this test');
        }

        if (count($stratigraphicUnits) < 2) {
            $this->markTestSkipped('Need at least 2 stratigraphic units from the same site for this test');
        }

        $relationIri = $relations[0]['@id'];

        // Find two different stratigraphic units from the same site
        $lftSu = null;
        $rgtSu = null;

        foreach ($stratigraphicUnits as $su1) {
            foreach ($stratigraphicUnits as $su2) {
                if ($su1['id'] !== $su2['id'] && $su1['site']['id'] === $su2['site']['id']) {
                    $lftSu = $su1;
                    $rgtSu = $su2;
                    break 2;
                }
            }
        }

        if (!$lftSu || !$rgtSu) {
            $this->markTestSkipped('Could not find two different stratigraphic units from the same site for this test');
        }

        // Test successful creation with valid data
        $response = $this->apiRequest($client, 'POST', '/api/data/stratigraphic_unit_relationships', [
            'token' => $token,
            'json' => [
                'lftStratigraphicUnit' => $lftSu['@id'],
                'relationship' => $relationIri,
                'rgtStratigraphicUnit' => $rgtSu['@id'],
            ],
        ]);

        $this->assertSame(201, $response->getStatusCode());
        $responseData = $response->toArray();

        $this->assertArrayHasKey('@id', $responseData);
        $this->assertArrayHasKey('lftStratigraphicUnit', $responseData);
        $this->assertArrayHasKey('relationship', $responseData);
        $this->assertArrayHasKey('rgtStratigraphicUnit', $responseData);

        $this->assertSame($lftSu['@id'], $responseData['lftStratigraphicUnit']['@id']);
        $this->assertSame($rgtSu['@id'], $responseData['rgtStratigraphicUnit']['@id']);
    }

    public function testGetStratigraphicUnitRelationshipCollection(): void
    {
        $client = self::createClient();
        $token = $this->getUserToken($client, 'user_editor');

        $response = $this->apiRequest($client, 'GET', '/api/data/stratigraphic_unit_relationships', [
            'token' => $token,
        ]);

        $this->assertSame(200, $response->getStatusCode());
        $data = $response->toArray();

        $this->assertArrayHasKey('member', $data);
        $this->assertArrayHasKey('@context', $data);
        $this->assertArrayHasKey('@id', $data);
        $this->assertArrayHasKey('@type', $data);
    }

    public function testGetStratigraphicUnitRelationshipItem(): void
    {
        $client = self::createClient();
        $token = $this->getUserToken($client, 'user_editor');

        // First get the collection to find an existing relationship
        $collectionResponse = $this->apiRequest($client, 'GET', '/api/data/stratigraphic_unit_relationships', [
            'token' => $token,
        ]);

        $this->assertSame(200, $collectionResponse->getStatusCode());
        $collectionData = $collectionResponse->toArray();

        if (empty($collectionData['member'])) {
            $this->markTestSkipped('No stratigraphic unit relationships available for this test');
        }

        $relationshipIri = $collectionData['member'][0]['@id'];

        // Test getting individual relationship
        $response = $this->apiRequest($client, 'GET', $relationshipIri, [
            'token' => $token,
        ]);

        $this->assertSame(200, $response->getStatusCode());
        $data = $response->toArray();

        $this->assertArrayHasKey('@id', $data);
        $this->assertArrayHasKey('lftStratigraphicUnit', $data);
        $this->assertArrayHasKey('relationship', $data);
        $this->assertArrayHasKey('rgtStratigraphicUnit', $data);
        $this->assertSame($relationshipIri, $data['@id']);
    }
}
