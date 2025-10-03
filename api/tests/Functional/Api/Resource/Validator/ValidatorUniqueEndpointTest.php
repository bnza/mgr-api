<?php

namespace App\Tests\Functional\Api\Resource\Validator;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Tests\Functional\ApiTestRequestTrait;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class ValidatorUniqueEndpointTest extends ApiTestCase
{
    use ApiTestRequestTrait;

    protected function setUp(): void
    {
        parent::setUp();
        static::$alwaysBootKernel = false;
        $this->parameterBag = self::getContainer()->get(ParameterBagInterface::class);
    }

    public function testValidatorUniqueSedimentCoreEndpointReturnFalseWhenCodeExists(): void
    {
        $client = self::createClient();

        $items = $this->getSedimentCores();
        $this->assertNotEmpty($items, 'Should have at least one sediment core for testing');

        $existingSedimentCore = $items[0];
        $siteId = basename($existingSedimentCore['site']['@id']);
        $year = $existingSedimentCore['year'];
        $number = $existingSedimentCore['number'];

        // Test existing code - should return unique: false
        $response = $this->apiRequest($client, 'GET', "/api/validator/unique/sediment_cores?site={$siteId}&year={$year}&number={$number}");

        $this->assertSame(200, $response->getStatusCode());
        $responseData = $response->toArray();

        $this->assertArrayHasKey('valid', $responseData);
        $this->assertSame(0, $responseData['valid'], 'Existing sediment core should not be unique');
    }

    public function testValidatorUniqueSedimentCoreEndpointReturnTrueWhenCodeNotExists(): void
    {
        $client = self::createClient();

        $items = $this->getSedimentCores();
        $this->assertNotEmpty($items, 'Should have at least one sediment core for testing');

        $existingSedimentCore = $items[0];
        $siteId = basename($existingSedimentCore['site']['@id']);
        $year = $existingSedimentCore['year'];
        $number = 9999;

        $response = $this->apiRequest($client, 'GET', "/api/validator/unique/sediment_cores?site={$siteId}&year={$year}&number={$number}");

        $this->assertSame(200, $response->getStatusCode());
        $responseData = $response->toArray();

        $this->assertArrayHasKey('valid', $responseData);
        $this->assertSame(1, $responseData['valid'], 'Non-existing sediment core should not be unique');
    }

    public function testValidatorUniqueSedimentCoreStratigraphicUnitEndpointReturnFalseWhenCodeExists(): void
    {
        $client = self::createClient();

        $items = $this->getSedimentCoreDepths();
        $this->assertNotEmpty($items, 'Should have at least one sediment core depths unit association for testing');

        $existingSedimentCore = basename($items[0]['sedimentCore']['@id']);
        $existingDepthMin = basename($items[0]['depthMin']);

        // Test existing code - should return unique: false
        $response = $this->apiRequest($client, 'GET', "/api/validator/unique/sediment_core_depths?sedimentCore={$existingSedimentCore}&depthMin={$existingDepthMin}");

        $this->assertSame(200, $response->getStatusCode());
        $responseData = $response->toArray();

        $this->assertArrayHasKey('valid', $responseData);
        $this->assertSame(0, $responseData['valid'], 'Existing sediment core depth unit association should not be unique');
    }

    public function testValidatorUniqueSedimentCoreStratigraphicUnitEndpointReturnTrueWhenCodeNotExists(): void
    {
        $client = self::createClient();

        $items = $this->getSedimentCoreDepths();
        $this->assertNotEmpty($items, 'Should have at least one sediment core depth unit association for testing');

        $existingSedimentCore = basename($items[0]['sedimentCore']['@id']);
        $existingDepthMin = 9999;

        // Test existing code - should return unique: false
        $response = $this->apiRequest($client, 'GET', "/api/validator/unique/sediment_core_depths?sedimentCore={$existingSedimentCore}&depthMin={$existingDepthMin}");

        $this->assertSame(200, $response->getStatusCode());
        $responseData = $response->toArray();

        $this->assertArrayHasKey('valid', $responseData);
        $this->assertSame(1, $responseData['valid'], 'Non-existing sediment core depth should not be unique');
    }

    public function testValidatorUniqueAnalysisSiteAnthropologyEndpointReturnFalseWhenCodeExists(): void
    {
        $client = self::createClient();

        $items = $this->getAnalysisAnthropology();
        $this->assertNotEmpty($items, 'Should have at least one analysis/anthropology for testing');

        $existingSubject = basename($items[0]['subject']['@id']);
        $existingAnalysis = basename($items[0]['analysis']['@id']);

        // Test existing code - should return unique: false
        $response = $this->apiRequest($client, 'GET', "/api/validator/unique/analyses/sites/anthropology?subject={$existingSubject}&analysis={$existingAnalysis}");

        $this->assertSame(200, $response->getStatusCode());
        $responseData = $response->toArray();

        $this->assertArrayHasKey('valid', $responseData);
        $this->assertSame(0, $responseData['valid'], 'Existing analysis/subject combination should not be unique');
    }

    public function testValidatorUniqueAnalysisSiteAnthropologyEndpointReturnTrueWhenCodeNotExists(): void
    {
        $client = self::createClient();

        $items = $this->getAnalysisAnthropology();
        $this->assertNotEmpty($items, 'Should have at least one analysis/MU for testing');

        $existingSubject = basename($items[0]['subject']['@id']);
        $existingAnalysis = 9999;

        // Test existing code - should return unique: false
        $response = $this->apiRequest($client, 'GET', "/api/validator/unique/analyses/sites/anthropology?subject={$existingSubject}&analysis={$existingAnalysis}");

        $this->assertSame(200, $response->getStatusCode());
        $responseData = $response->toArray();

        $this->assertArrayHasKey('valid', $responseData);
        $this->assertSame(1, $responseData['valid'], 'Non-existing analysis/subject combination should be unique');
    }

    public function testValidatorUniqueIndividualIdentifierEndpointReturnFalseWhenInventoryExists(): void
    {
        $client = self::createClient();

        // Get existing potteries
        $potteries = $this->getIndividuals();
        $this->assertNotEmpty($potteries, 'Should have at least one individual item for testing');

        $firstItem = $potteries[0];
        $existingIdentifier = $firstItem['identifier'];

        // Test existing identifier - should return valid: false (0)
        $response = $this->apiRequest($client, 'GET', "/api/validator/unique/individuals/identifier?identifier={$existingIdentifier}");

        $this->assertSame(200, $response->getStatusCode());
        $responseData = $response->toArray();

        $this->assertArrayHasKey('valid', $responseData);
        $this->assertSame(0, $responseData['valid'], 'Existing individual identifier should not be unique');
    }

    public function testValidatorUniqueIndividualIdentifierEndpointReturnTrueWhenInventoryNotExists(): void
    {
        $client = self::createClient();

        // Test with a non-existing inventory code - should return valid: true (1)
        $nonExistentIdentifier = 'NONEXISTENT_IDENTIFIFER_'.uniqid();

        $response = $this->apiRequest($client, 'GET', "/api/validator/unique/individuals/identifier?identifier={$nonExistentIdentifier}");

        $this->assertSame(200, $response->getStatusCode());
        $responseData = $response->toArray();

        $this->assertArrayHasKey('valid', $responseData);
        $this->assertSame(1, $responseData['valid'], 'Non-existing individual identifier should be unique');
    }

    public function testValidatorUniqueAnalysisMicrostratigraphicUnitEndpointReturnFalseWhenCodeExists(): void
    {
        $client = self::createClient();

        $items = $this->getAnalysisMicrostratigraphicUnits();
        $this->assertNotEmpty($items, 'Should have at least one analysis/MU for testing');

        $existingSubject = basename($items[0]['subject']['@id']);
        $existingAnalysis = basename($items[0]['analysis']['@id']);

        // Test existing code - should return unique: false
        $response = $this->apiRequest($client, 'GET', "/api/validator/unique/analyses/samples/microstratigraphy?subject={$existingSubject}&analysis={$existingAnalysis}");

        $this->assertSame(200, $response->getStatusCode());
        $responseData = $response->toArray();

        $this->assertArrayHasKey('valid', $responseData);
        $this->assertSame(0, $responseData['valid'], 'Existing analysis/subject combination should not be unique');
    }

    public function testValidatorUniqueAnalysisSampleMicrostratigraphicUnitEndpointReturnTrueWhenCodeNotExists(): void
    {
        $client = self::createClient();

        $items = $this->getAnalysisMicrostratigraphicUnits();
        $this->assertNotEmpty($items, 'Should have at least one analysis/MU for testing');

        $existingSubject = basename($items[0]['subject']['@id']);
        $existingAnalysis = 9999;

        // Test existing code - should return unique: false
        $response = $this->apiRequest($client, 'GET', "/api/validator/unique/analyses/samples/microstratigraphy?subject={$existingSubject}&analysis={$existingAnalysis}");

        $this->assertSame(200, $response->getStatusCode());
        $responseData = $response->toArray();

        $this->assertArrayHasKey('valid', $responseData);
        $this->assertSame(1, $responseData['valid'], 'Existing analysis/subject combination should not be unique');
    }

    public function testValidatorUniqueMicrostratigraphicUnitEndpointReturnFalseWhenCodeExists(): void
    {
        $client = self::createClient();

        $items = $this->getMicrostratigraphicUnits();
        $this->assertNotEmpty($items, 'Should have at least one MU for testing');

        $existingSu = basename($items[0]['stratigraphicUnit']['@id']);
        $existingIdentifier = $items[0]['identifier'];

        // Test existing code - should return unique: false
        $response = $this->apiRequest($client, 'GET', "/api/validator/unique/microstratigraphic_units?stratigraphicUnit={$existingSu}&identifier={$existingIdentifier}");

        $this->assertSame(200, $response->getStatusCode());
        $responseData = $response->toArray();

        $this->assertArrayHasKey('valid', $responseData);
        $this->assertSame(0, $responseData['valid'], 'Existing SU/identifier combination should not be unique');
    }

    public function testValidatorUniqueMicrostratigraphicUnitEndpointReturnTrueWhenCodeNotExists(): void
    {
        $client = self::createClient();

        // Test with a non-existing site code - should return unique: true
        $items = $this->getMicrostratigraphicUnits();
        $this->assertNotEmpty($items, 'Should have at least one MU for testing');

        $existingSu = basename($items[0]['stratigraphicUnit']['@id']);
        $nonExistentSha256 = uniqid();

        $response = $this->apiRequest($client, 'GET', "/api/validator/unique/microstratigraphic_units?stratigraphicUnit={$existingSu}&identifier={$nonExistentSha256}");

        $this->assertSame(200, $response->getStatusCode());
        $responseData = $response->toArray();

        $this->assertArrayHasKey('valid', $responseData);
        $this->assertSame(1, $responseData['valid'], 'Non-existing sha256 code should be unique');
    }

    public function testValidatorUniqueMediaObjectSha256EndpointReturnFalseWhenCodeExists(): void
    {
        $client = self::createClient();

        // Test with an existing site code
        $sites = $this->getMediaObject();
        $this->assertNotEmpty($sites, 'Should have at least one media object for testing');

        $existingSha256 = $sites[0]['sha256'];

        // Test existing code - should return unique: false
        $response = $this->apiRequest($client, 'GET', "/api/validator/unique/media_objects/sha256?sha256={$existingSha256}");

        $this->assertSame(200, $response->getStatusCode());
        $responseData = $response->toArray();

        $this->assertArrayHasKey('valid', $responseData);
        $this->assertSame(0, $responseData['valid'], 'Existing sha256 should not be unique');
    }

    public function testValidatorUniqueObjectSha256EndpointReturnTrueWhenCodeNotExists(): void
    {
        $client = self::createClient();

        // Test with a non-existing site code - should return unique: true
        $nonExistentSha256 = hash('sha256', uniqid());

        $response = $this->apiRequest($client, 'GET', "/api/validator/unique/media_objects/sha256?sha256={$nonExistentSha256}");

        $this->assertSame(200, $response->getStatusCode());
        $responseData = $response->toArray();

        $this->assertArrayHasKey('valid', $responseData);
        $this->assertSame(1, $responseData['valid'], 'Non-existing sha256 code should be unique');
    }

    public function testValidatorUniqueSiteCodeEndpointReturnFalseWhenCodeExists(): void
    {
        $client = self::createClient();

        // Test with an existing site code
        $sites = $this->getSites();
        $this->assertNotEmpty($sites, 'Should have at least one site for testing');

        $existingSiteCode = $sites[0]['code'];

        // Test existing code - should return unique: false
        $response = $this->apiRequest($client, 'GET', "/api/validator/unique/sites/code?code={$existingSiteCode}");

        $this->assertSame(200, $response->getStatusCode());
        $responseData = $response->toArray();

        $this->assertArrayHasKey('valid', $responseData);
        $this->assertSame(0, $responseData['valid'], 'Existing site code should not be unique');
    }

    public function testValidatorUniqueSiteCodeEndpointReturnTrueWhenCodeNotExists(): void
    {
        $client = self::createClient();

        // Test with a non-existing site code - should return unique: true
        $nonExistentCode = 'NONEXISTENT'.uniqid();

        $response = $this->apiRequest($client, 'GET', "/api/validator/unique/sites/code?code={$nonExistentCode}");

        $this->assertSame(200, $response->getStatusCode());
        $responseData = $response->toArray();

        $this->assertArrayHasKey('valid', $responseData);
        $this->assertSame(1, $responseData['valid'], 'Non-existing site code should be unique');
    }

    public function testValidatorUniqueSiteUserPrivilegeEndpointReturnFalseWhenCombinationExists(): void
    {
        $client = self::createClient();

        // Get the first site user privilege to use its site and user IDs
        $siteUserPrivileges = $this->getSiteUserPrivileges();
        $this->assertNotEmpty($siteUserPrivileges, 'Should have at least one site user privilege for testing');

        $firstPrivilege = $siteUserPrivileges[0];

        // Extract site ID and user ID from the privilege
        $siteId = $firstPrivilege['site']['id'];
        $userId = $firstPrivilege['user']['id'];

        // Test existing site-user combination - should return valid: false (0)
        $response = $this->apiRequest($client, 'GET', "/api/validator/unique/site_user_privileges?site={$siteId}&user={$userId}");

        $this->assertSame(200, $response->getStatusCode());
        $responseData = $response->toArray();

        $this->assertArrayHasKey('valid', $responseData);
        $this->assertSame(0, $responseData['valid'], 'Existing site-user combination should not be unique');
    }

    public function testValidatorUniqueStratigraphicEndpointReturnTrueWhenCodeNotExists(): void
    {
        $client = self::createClient();
        $siteId = $this->getSites()[0]['id'];
        $year = 2023;
        $number = 9999;
        $response = $this->apiRequest($client, 'GET', "/api/validator/unique/stratigraphic_units?site={$siteId}&year={$year}&number={$number}");

        $this->assertSame(200, $response->getStatusCode());
        $responseData = $response->toArray();

        $this->assertArrayHasKey('valid', $responseData);
        $this->assertSame(1, $responseData['valid'], 'Non-existing SU combination should be unique');
    }

    public function testValidatorUniqueStratigraphicEndpointReturnFalseWhenCombinationExists(): void
    {
        $client = self::createClient();

        // Get the first site user privilege to use its site and user IDs
        $stratigraphicUnits = $this->getSiteStratigraphicUnits();
        $this->assertNotEmpty($stratigraphicUnits, 'Should have at least one site user privilege for testing');

        $firstStratigraphicUnit = $stratigraphicUnits[0];

        // Extract data
        $siteId = $firstStratigraphicUnit['site']['id'];
        $year = $firstStratigraphicUnit['year'];
        $number = $firstStratigraphicUnit['number'];

        // Test existing site-user combination - should return valid: false (0)
        $response = $this->apiRequest($client, 'GET', "/api/validator/unique/stratigraphic_units?site={$siteId}&year={$year}&number={$number}");

        $this->assertSame(200, $response->getStatusCode());
        $responseData = $response->toArray();

        $this->assertArrayHasKey('valid', $responseData);
        $this->assertSame(0, $responseData['valid'], 'Existing stratigraphic combination should not be unique');
    }

    public function testValidatorUniqueStratigraphicEndpointReturnFalseWhenCombinationExistsAlsoWhenStratigraphicUnitHasDefaultValue(): void
    {
        $client = self::createClient();
        $token = $this->getUserToken($client, 'user_admin');
        $site = $this->getSites()[0];

        // Create first stratigraphic unit
        $payload = [
            'site' => $site['@id'],
            'number' => 9999,
            'description' => 'First unit',
            'interpretation' => 'First interpretation',
        ];

        $createResponse = $this->apiRequest($client, 'POST', '/api/data/stratigraphic_units', [
            'token' => $token,
            'json' => $payload,
        ]);
        $this->assertSame(201, $createResponse->getStatusCode());

        // Get the first site user privilege to use its site and user IDs
        $stratigraphicUnits = $createResponse->toArray();
        $this->assertNotEmpty($stratigraphicUnits, 'Should have at least one site user privilege for testing');

        // Extract data
        $siteId = $stratigraphicUnits['site']['id'];
        $year = $stratigraphicUnits['year'];
        $number = $stratigraphicUnits['number'];

        // Test existing site-user combination - should return valid: false (0)
        $response = $this->apiRequest($client, 'GET', "/api/validator/unique/stratigraphic_units?site={$siteId}&year={$year}&number={$number}");

        $this->assertSame(200, $response->getStatusCode());
        $responseData = $response->toArray();

        $this->assertArrayHasKey('valid', $responseData);
        $this->assertSame(0, $responseData['valid'], 'Existing stratigraphic combination should not be unique');
    }

    public function testValidatorUniqueContextStratigraphicUnitEndpointReturnFalseWhenCombinationExists(): void
    {
        $client = self::createClient();

        // Get existing context-stratigraphic unit relationships
        $contextStratigraphicUnits = $this->getContextStratigraphicUnits();
        $this->assertNotEmpty($contextStratigraphicUnits, 'Should have at least one context-stratigraphic unit relationship for testing');

        $firstRelationship = $contextStratigraphicUnits[0];

        // Extract context ID and stratigraphic unit ID
        $contextId = $firstRelationship['context']['id'];
        $stratigraphicUnitId = $firstRelationship['stratigraphicUnit']['id'];

        // Test existing context-stratigraphic unit combination - should return valid: false (0)
        $response = $this->apiRequest($client, 'GET', "/api/validator/unique/context_stratigraphic_units?context={$contextId}&stratigraphicUnit={$stratigraphicUnitId}");

        $this->assertSame(200, $response->getStatusCode());
        $responseData = $response->toArray();

        $this->assertArrayHasKey('valid', $responseData);
        $this->assertSame(0, $responseData['valid'], 'Existing context-stratigraphic unit combination should not be unique');
    }

    public function testValidatorUniqueContextStratigraphicUnitEndpointReturnTrueWhenCombinationNotExists(): void
    {
        $client = self::createClient();

        // Get contexts and stratigraphic units to create a non-existing combination
        $contexts = $this->getContexts();
        $stratigraphicUnits = $this->getSiteStratigraphicUnits();

        $this->assertNotEmpty($contexts, 'Should have at least one context for testing');
        $this->assertNotEmpty($stratigraphicUnits, 'Should have at least one stratigraphic unit for testing');

        // Use very high IDs that are unlikely to exist in combination
        $contextId = 999999;
        $stratigraphicUnitId = 999999;

        // Test non-existing context-stratigraphic unit combination - should return valid: true (1)
        $response = $this->apiRequest($client, 'GET', "/api/validator/unique/context_stratigraphic_units?context={$contextId}&stratigraphicUnit={$stratigraphicUnitId}");

        $this->assertSame(200, $response->getStatusCode());
        $responseData = $response->toArray();

        $this->assertArrayHasKey('valid', $responseData);
        $this->assertSame(1, $responseData['valid'], 'Non-existing context-stratigraphic unit combination should be unique');
    }

    public function testValidatorUniqueContextStratigraphicUnitEndpointWithInvalidContextId(): void
    {
        $client = self::createClient();

        // Get a valid stratigraphic unit ID
        $stratigraphicUnits = $this->getSiteStratigraphicUnits();
        $this->assertNotEmpty($stratigraphicUnits, 'Should have at least one stratigraphic unit for testing');

        $validStratigraphicUnitId = $stratigraphicUnits[0]['id'];
        $invalidContextId = 999999;

        // Test with invalid context ID - should return valid: true (1) since combination doesn't exist
        $response = $this->apiRequest($client, 'GET', "/api/validator/unique/context_stratigraphic_units?context={$invalidContextId}&stratigraphicUnit={$validStratigraphicUnitId}");

        $this->assertSame(200, $response->getStatusCode());
        $responseData = $response->toArray();

        $this->assertArrayHasKey('valid', $responseData);
        $this->assertSame(1, $responseData['valid'], 'Combination with invalid context ID should be unique');
    }

    public function testValidatorUniqueContextStratigraphicUnitEndpointWithInvalidStratigraphicUnitId(): void
    {
        $client = self::createClient();

        // Get a valid context ID
        $contexts = $this->getContexts();
        $this->assertNotEmpty($contexts, 'Should have at least one context for testing');

        $validContextId = $contexts[0]['id'];
        $invalidStratigraphicUnitId = 999999;

        // Test with invalid stratigraphic unit ID - should return valid: true (1) since combination doesn't exist
        $response = $this->apiRequest($client, 'GET', "/api/validator/unique/context_stratigraphic_units?context={$validContextId}&stratigraphicUnit={$invalidStratigraphicUnitId}");

        $this->assertSame(200, $response->getStatusCode());
        $responseData = $response->toArray();

        $this->assertArrayHasKey('valid', $responseData);
        $this->assertSame(1, $responseData['valid'], 'Combination with invalid stratigraphic unit ID should be unique');
    }

    public function testValidatorUniqueSampleStratigraphicUnitsEndpointReturnFalseWhenCombinationExists(): void
    {
        $client = self::createClient();

        // Get existing sample-stratigraphic unit relationships
        $sampleStratigraphicUnits = $this->getSampleStratigraphicUnits();
        $this->assertNotEmpty($sampleStratigraphicUnits, 'Should have at least one sample-stratigraphic unit relationship for testing');

        $firstRelationship = $sampleStratigraphicUnits[0];

        // Extract sample ID and stratigraphic unit ID
        $sampleId = basename($firstRelationship['sample']['@id']);
        $stratigraphicUnitId = basename($firstRelationship['stratigraphicUnit']['@id']);

        // Test existing sample-stratigraphic unit combination - should return valid: false (0)
        $response = $this->apiRequest($client, 'GET', "/api/validator/unique/sample_stratigraphic_units?sample={$sampleId}&stratigraphicUnit={$stratigraphicUnitId}");

        $this->assertSame(200, $response->getStatusCode());
        $responseData = $response->toArray();

        $this->assertArrayHasKey('valid', $responseData);
        $this->assertSame(0, $responseData['valid'], 'Existing sample-stratigraphic unit combination should not be unique');
    }

    public function testValidatorUniqueSampleStratigraphicUnitsEndpointReturnTrueWhenCombinationNotExists(): void
    {
        $client = self::createClient();

        // Get samples and stratigraphic units to create a non-existing combination
        $samples = $this->getSamples();
        $stratigraphicUnits = $this->getSiteStratigraphicUnits();

        $this->assertNotEmpty($samples, 'Should have at least one sample for testing');
        $this->assertNotEmpty($stratigraphicUnits, 'Should have at least one stratigraphic unit for testing');

        // Use very high IDs that are unlikely to exist in combination
        $sampleId = 999999;
        $stratigraphicUnitId = 999999;

        // Test non-existing sample-stratigraphic unit combination - should return valid: true (1)
        $response = $this->apiRequest($client, 'GET', "/api/validator/unique/sample_stratigraphic_units?sample={$sampleId}&stratigraphicUnit={$stratigraphicUnitId}");

        $this->assertSame(200, $response->getStatusCode());
        $responseData = $response->toArray();

        $this->assertArrayHasKey('valid', $responseData);
        $this->assertSame(1, $responseData['valid'], 'Non-existing sample-stratigraphic unit combination should be unique');
    }

    public function testValidatorUniqueSampleStratigraphicUnitsEndpointWithInvalidSampleId(): void
    {
        $client = self::createClient();

        // Get a valid stratigraphic unit ID
        $stratigraphicUnits = $this->getSiteStratigraphicUnits();
        $this->assertNotEmpty($stratigraphicUnits, 'Should have at least one stratigraphic unit for testing');

        $validStratigraphicUnitId = $stratigraphicUnits[0]['id'];
        $invalidSampleId = 999999;

        // Test with invalid sample ID - should return valid: true (1) since combination doesn't exist
        $response = $this->apiRequest($client, 'GET', "/api/validator/unique/sample_stratigraphic_units?sample={$invalidSampleId}&stratigraphicUnit={$validStratigraphicUnitId}");

        $this->assertSame(200, $response->getStatusCode());
        $responseData = $response->toArray();

        $this->assertArrayHasKey('valid', $responseData);
        $this->assertSame(1, $responseData['valid'], 'Combination with invalid sample ID should be unique');
    }

    public function testValidatorUniqueSampleStratigraphicUnitsEndpointWithInvalidStratigraphicUnitId(): void
    {
        $client = self::createClient();

        // Get a valid sample ID
        $samples = $this->getSamples();
        $this->assertNotEmpty($samples, 'Should have at least one sample for testing');

        $validSampleId = $samples[0]['id'];
        $invalidStratigraphicUnitId = 999999;

        // Test with invalid stratigraphic unit ID - should return valid: true (1) since combination doesn't exist
        $response = $this->apiRequest($client, 'GET', "/api/validator/unique/sample_stratigraphic_units?sample={$validSampleId}&stratigraphicUnit={$invalidStratigraphicUnitId}");

        $this->assertSame(200, $response->getStatusCode());
        $responseData = $response->toArray();

        $this->assertArrayHasKey('valid', $responseData);
        $this->assertSame(1, $responseData['valid'], 'Combination with invalid stratigraphic unit ID should be unique');
    }

    public function testValidatorUniqueSamplesEndpointReturnFalseWhenCombinationExists(): void
    {
        $client = self::createClient();

        // Get existing samples
        $samples = $this->getSamples();
        $this->assertNotEmpty($samples, 'Should have at least one sample for testing');

        $firstSample = $samples[0];

        // Extract sample data
        $siteId = $firstSample['site']['id'];
        $typeId = basename($firstSample['type']['@id']);
        $year = $firstSample['year'];
        $number = $firstSample['number'];

        // Test existing sample combination - should return valid: false (0)
        $response = $this->apiRequest($client, 'GET', "/api/validator/unique/samples?site={$siteId}&type={$typeId}&year={$year}&number={$number}");

        $this->assertSame(200, $response->getStatusCode());
        $responseData = $response->toArray();

        $this->assertArrayHasKey('valid', $responseData);
        $this->assertSame(0, $responseData['valid'], 'Existing sample combination should not be unique');
    }

    public function testValidatorUniqueSamplesEndpointReturnTrueWhenCombinationNotExists(): void
    {
        $client = self::createClient();

        // Get sites and sample types to create a non-existing combination
        $sites = $this->getSites();
        $sampleTypes = $this->getVocabulary(['sample', 'types']);

        $this->assertNotEmpty($sites, 'Should have at least one site for testing');
        $this->assertNotEmpty($sampleTypes, 'Should have at least one sample type for testing');

        // Use existing site and type but with unlikely year/number combination
        $siteId = $sites[0]['id'];
        $typeId = $sampleTypes[0]['id'];
        $year = 2023;
        $number = 9999;

        // Test non-existing sample combination - should return valid: true (1)
        $response = $this->apiRequest($client, 'GET', "/api/validator/unique/samples?site={$siteId}&type={$typeId}&year={$year}&number={$number}");

        $this->assertSame(200, $response->getStatusCode());
        $responseData = $response->toArray();

        $this->assertArrayHasKey('valid', $responseData);
        $this->assertSame(1, $responseData['valid'], 'Non-existing sample combination should be unique');
    }

    public function testValidatorUniqueSamplesEndpointWithInvalidSiteId(): void
    {
        $client = self::createClient();

        // Get valid sample type
        $sampleTypes = $this->getVocabulary(['sample', 'types']);
        $this->assertNotEmpty($sampleTypes, 'Should have at least one sample type for testing');

        $invalidSiteId = 999999;
        $validTypeId = $sampleTypes[0]['id'];
        $year = 2023;
        $number = 1;

        // Test with invalid site ID - should return valid: true (1) since combination doesn't exist
        $response = $this->apiRequest($client, 'GET', "/api/validator/unique/samples?site={$invalidSiteId}&type={$validTypeId}&year={$year}&number={$number}");

        $this->assertSame(200, $response->getStatusCode());
        $responseData = $response->toArray();

        $this->assertArrayHasKey('valid', $responseData);
        $this->assertSame(1, $responseData['valid'], 'Combination with invalid site ID should be unique');
    }

    public function testValidatorUniqueSamplesEndpointWithInvalidTypeId(): void
    {
        $client = self::createClient();

        // Get valid site
        $sites = $this->getSites();
        $this->assertNotEmpty($sites, 'Should have at least one site for testing');

        $validSiteId = $sites[0]['id'];
        $invalidTypeId = 9999;
        $year = 2023;
        $number = 1;

        // Test with invalid type ID - should return valid: true (1) since combination doesn't exist
        $response = $this->apiRequest($client, 'GET', "/api/validator/unique/samples?site={$validSiteId}&type={$invalidTypeId}&year={$year}&number={$number}");

        $this->assertSame(200, $response->getStatusCode());
        $responseData = $response->toArray();

        $this->assertArrayHasKey('valid', $responseData);
        $this->assertSame(1, $responseData['valid'], 'Combination with invalid type ID should be unique');
    }

    public function testValidatorUniquePotteriesInventoryEndpointReturnFalseWhenInventoryExists(): void
    {
        $client = self::createClient();

        // Get existing potteries
        $potteries = $this->getPotteries();
        $this->assertNotEmpty($potteries, 'Should have at least one pottery item for testing');

        $firstPottery = $potteries[0];
        $existingInventory = $firstPottery['inventory'];

        // Test existing inventory - should return valid: false (0)
        $response = $this->apiRequest($client, 'GET', "/api/validator/unique/potteries/inventory?inventory={$existingInventory}");

        $this->assertSame(200, $response->getStatusCode());
        $responseData = $response->toArray();

        $this->assertArrayHasKey('valid', $responseData);
        $this->assertSame(0, $responseData['valid'], 'Existing pottery inventory should not be unique');
    }

    public function testValidatorUniquePotteriesInventoryEndpointReturnFalseWhenForwardSlashedInventoryExists(): void
    {
        $client = self::createClient();

        // Test existing slashed inventory from fixtures - should return valid: false (0)
        $response = $this->apiRequest($client, 'GET', '/api/validator/unique/potteries/inventory?inventory=ME002/2023');

        $this->assertSame(200, $response->getStatusCode());
        $responseData = $response->toArray();

        $this->assertArrayHasKey('valid', $responseData);
        $this->assertSame(0, $responseData['valid'], 'Existing pottery inventory should not be unique');
    }

    public function testValidatorUniquePotteriesInventoryEndpointReturnTrueWhenInventoryNotExists(): void
    {
        $client = self::createClient();

        // Test with a non-existing inventory code - should return valid: true (1)
        $nonExistentInventory = 'NONEXISTENT_INVENTORY_'.uniqid();

        $response = $this->apiRequest($client, 'GET', "/api/validator/unique/potteries/inventory?inventory={$nonExistentInventory}");

        $this->assertSame(200, $response->getStatusCode());
        $responseData = $response->toArray();

        $this->assertArrayHasKey('valid', $responseData);
        $this->assertSame(1, $responseData['valid'], 'Non-existing pottery inventory should be unique');
    }

    public function testValidatorUniqueAnalysesPotteriesEndpointReturnFalseWhenCombinationExists(): void
    {
        $client = self::createClient();

        // Get existing pottery analyses
        $potteryAnalyses = $this->getPotteryAnalyses();
        $this->assertNotEmpty($potteryAnalyses, 'Should have at least one pottery analysis for testing');

        $firstPotteryAnalysis = $potteryAnalyses[0];

        // Extract pottery ID and analysis type ID from the existing analysis
        $potteryId = basename($firstPotteryAnalysis['subject']['@id']);
        $analysisId = basename($firstPotteryAnalysis['analysis']['@id']);

        // Test existing pottery-analysis type combination - should return valid: false (0)
        $response = $this->apiRequest($client, 'GET', "/api/validator/unique/analyses/potteries?analysis={$analysisId}&subject={$potteryId}");

        $this->assertSame(200, $response->getStatusCode());
        $responseData = $response->toArray();

        $this->assertArrayHasKey('valid', $responseData);
        $this->assertSame(0, $responseData['valid'], 'Existing pottery-analysis type combination should not be unique');
    }

    public function testValidatorUniqueAnalysesPotteriesEndpointReturnTrueWhenCombinationNotExists(): void
    {
        $client = self::createClient();

        // Get potteries and analysis types to create a non-existing combination
        $potteries = $this->getPotteries();
        $analysisTypes = $this->getVocabulary(['analysis', 'types']);

        $this->assertNotEmpty($potteries, 'Should have at least one pottery for testing');
        $this->assertNotEmpty($analysisTypes, 'Should have at least one analysis type for testing');

        // Use existing pottery and type but create a combination that doesn't exist
        // We'll use high IDs that are unlikely to exist in combination
        $potteryId = 999999;
        $typeId = 9999;

        // Test non-existing pottery-analysis type combination - should return valid: true (1)
        $response = $this->apiRequest($client, 'GET', "/api/validator/unique/analyses/potteries?analysis={$potteryId}&subject={$typeId}");

        $this->assertSame(200, $response->getStatusCode());
        $responseData = $response->toArray();

        $this->assertArrayHasKey('valid', $responseData);
        $this->assertSame(1, $responseData['valid'], 'Non-existing pottery-analysis type combination should be unique');
    }

    public function testValidatorUniqueMediaObjectStratigraphicUnitsEndpointReturnFalseWhenCombinationExists(): void
    {
        $client = self::createClient();

        // Get existing media object-stratigraphic unit relationships
        $mediaObjectStratigraphicUnits = $this->getMediaObjectStratigraphicUnits();
        $this->assertNotEmpty($mediaObjectStratigraphicUnits, 'Should have at least one media object-stratigraphic unit relationship for testing');

        $firstRelationship = $mediaObjectStratigraphicUnits[0];

        // Extract media object ID and stratigraphic unit ID
        $mediaObjectId = $firstRelationship['mediaObject']['id'];
        $stratigraphicUnitId = $firstRelationship['item']['id'];

        // Test existing media object-stratigraphic unit combination - should return valid: false (0)
        $response = $this->apiRequest($client, 'GET', "/api/validator/unique/media_objects/stratigraphic_units?mediaObject={$mediaObjectId}&item={$stratigraphicUnitId}");

        $this->assertSame(200, $response->getStatusCode());
        $responseData = $response->toArray();

        $this->assertArrayHasKey('valid', $responseData);
        $this->assertSame(0, $responseData['valid'], 'Existing media object-stratigraphic unit combination should not be unique');
    }

    public function testValidatorUniqueMediaObjectStratigraphicUnitsEndpointReturnTrueWhenCombinationNotExists(): void
    {
        $client = self::createClient();

        // Use very high IDs that are unlikely to exist in combination
        $mediaObjectId = 999999;
        $stratigraphicUnitId = 999999;

        // Test non-existing media object-stratigraphic unit combination - should return valid: true (1)
        $response = $this->apiRequest($client, 'GET', "/api/validator/unique/media_objects/stratigraphic_units?mediaObject={$mediaObjectId}&item={$stratigraphicUnitId}");

        $this->assertSame(200, $response->getStatusCode());
        $responseData = $response->toArray();

        $this->assertArrayHasKey('valid', $responseData);
        $this->assertSame(1, $responseData['valid'], 'Non-existing media object-stratigraphic unit combination should be unique');
    }

    public function testValidatorUniqueStratigraphicUnitRelationshipsEndpointReturnFalseWhenCombinationExists(): void
    {
        $client = self::createClient();

        // Get existing stratigraphic unit relationships
        $stratigraphicUnitRelationships = $this->getStratigraphicUnitRelationships();

        if (empty($stratigraphicUnitRelationships)) {
            $this->markTestSkipped('No stratigraphic unit relationships available for this test');
        }

        $firstRelationship = $stratigraphicUnitRelationships[0];

        // Extract lftSu and rgtSu IDs from the existing relationship
        $lftSuId = basename($firstRelationship['lftStratigraphicUnit']['@id']);
        $rgtSuId = basename($firstRelationship['rgtStratigraphicUnit']['@id']);

        // Test existing stratigraphic unit relationship combination - should return valid: false (0)
        $response = $this->apiRequest($client, 'GET', "/api/validator/unique/stratigraphic_unit_relationships?lftStratigraphicUnit={$lftSuId}&rgtStratigraphicUnit={$rgtSuId}");

        $this->assertSame(200, $response->getStatusCode());
        $responseData = $response->toArray();

        $this->assertArrayHasKey('valid', $responseData);
        $this->assertSame(0, $responseData['valid'], 'Existing stratigraphic unit relationship combination should not be unique');
    }

    public function testValidatorUniqueStratigraphicUnitRelationshipsEndpointReturnTrueWhenCombinationNotExists(): void
    {
        $client = self::createClient();

        // Get stratigraphic units to create a non-existing combination
        $stratigraphicUnits = $this->getSiteStratigraphicUnits();
        $this->assertNotEmpty($stratigraphicUnits, 'Should have at least one stratigraphic unit for testing');

        // Use very high IDs that are unlikely to exist in combination
        $lftSuId = 999999;
        $rgtSuId = 999999;

        // Test non-existing stratigraphic unit relationship combination - should return valid: true (1)
        $response = $this->apiRequest($client, 'GET', "/api/validator/unique/stratigraphic_unit_relationships?lftStratigraphicUnit={$lftSuId}&rgtStratigraphicUnit={$rgtSuId}");

        $this->assertSame(200, $response->getStatusCode());
        $responseData = $response->toArray();

        $this->assertArrayHasKey('valid', $responseData);
        $this->assertSame(1, $responseData['valid'], 'Non-existing stratigraphic unit relationship combination should be unique');
    }

    public function testValidatorUniqueStratigraphicUnitRelationshipsEndpointWithInvalidLftSuId(): void
    {
        $client = self::createClient();

        // Get a valid rgtSu ID
        $stratigraphicUnits = $this->getSiteStratigraphicUnits();
        $this->assertNotEmpty($stratigraphicUnits, 'Should have at least one stratigraphic unit for testing');

        $validRgtSuId = basename($stratigraphicUnits[0]['@id']);
        $invalidLftSuId = 999999;

        // Test with invalid lftSu ID - should return valid: true (1) since combination doesn't exist
        $response = $this->apiRequest($client, 'GET', "/api/validator/unique/stratigraphic_unit_relationships?lftStratigraphicUnit={$invalidLftSuId}&rgtStratigraphicUnit={$validRgtSuId}");

        $this->assertSame(200, $response->getStatusCode());
        $responseData = $response->toArray();

        $this->assertArrayHasKey('valid', $responseData);
        $this->assertSame(1, $responseData['valid'], 'Combination with invalid lftSu ID should be unique');
    }

    public function testValidatorUniqueStratigraphicUnitRelationshipsEndpointWithInvalidRgtSuId(): void
    {
        $client = self::createClient();

        // Get a valid lftSu ID
        $stratigraphicUnits = $this->getSiteStratigraphicUnits();
        $this->assertNotEmpty($stratigraphicUnits, 'Should have at least one stratigraphic unit for testing');

        $validLftSuId = basename($stratigraphicUnits[0]['id']);
        $invalidRgtSuId = 999999;

        // Test with invalid rgtSu ID - should return valid: true (1) since combination doesn't exist
        $response = $this->apiRequest($client, 'GET', "/api/validator/unique/stratigraphic_unit_relationships?lftStratigraphicUnit={$validLftSuId}&rgtStratigraphicUnit={$invalidRgtSuId}");

        $this->assertSame(200, $response->getStatusCode());
        $responseData = $response->toArray();

        $this->assertArrayHasKey('valid', $responseData);
        $this->assertSame(1, $responseData['valid'], 'Combination with invalid rgtSu ID should be unique');
    }

    public function testValidatorUniqueAnalysesEndpointReturnFalseWhenCombinationExists(): void
    {
        $client = self::createClient();

        // Get existing analyses
        $analyses = $this->getAnalyses();
        $this->assertNotEmpty($analyses, 'Should have at least one analysis for testing');

        $firstAnalysis = $analyses[0];

        // Extract analysis type ID and identifier from the existing analysis
        $typeId = basename($firstAnalysis['type']['@id']);
        $identifier = $firstAnalysis['identifier'];

        // Test existing analysis type-identifier combination - should return valid: false (0)
        $response = $this->apiRequest($client, 'GET', "/api/validator/unique/analyses?type={$typeId}&identifier=".urlencode($identifier));

        $this->assertSame(200, $response->getStatusCode());
        $responseData = $response->toArray();

        $this->assertArrayHasKey('valid', $responseData);
        $this->assertSame(0, $responseData['valid'], 'Existing analysis type-identifier combination should not be unique');
    }

    public function testValidatorUniqueAnalysesEndpointReturnTrueWhenCombinationNotExists(): void
    {
        $client = self::createClient();

        // Get analysis types to create a non-existing combination
        $analysisTypes = $this->getVocabulary(['analysis', 'types']);
        $this->assertNotEmpty($analysisTypes, 'Should have at least one analysis type for testing');

        // Use existing type but with non-existing identifier
        $typeId = $analysisTypes[0]['id'];
        $nonExistentIdentifier = 'NONEXISTENT_IDENTIFIER_'.uniqid();

        // Test non-existing analysis type-identifier combination - should return valid: true (1)
        $response = $this->apiRequest($client, 'GET', "/api/validator/unique/analyses?type={$typeId}&identifier=".urlencode($nonExistentIdentifier));

        $this->assertSame(200, $response->getStatusCode());
        $responseData = $response->toArray();

        $this->assertArrayHasKey('valid', $responseData);
        $this->assertSame(1, $responseData['valid'], 'Non-existing analysis type-identifier combination should be unique');
    }

    public function testValidatorUniqueAnalysesEndpointWithInvalidTypeId(): void
    {
        $client = self::createClient();

        // Use invalid type ID with any identifier
        $invalidTypeId = 9999;
        $identifier = 'TEST.IDENTIFIER';

        // Test with invalid type ID - should return valid: true (1) since combination doesn't exist
        $response = $this->apiRequest($client, 'GET', "/api/validator/unique/analyses?type={$invalidTypeId}&identifier=".urlencode($identifier));

        $this->assertSame(200, $response->getStatusCode());
        $responseData = $response->toArray();

        $this->assertArrayHasKey('valid', $responseData);
        $this->assertSame(1, $responseData['valid'], 'Combination with invalid type ID should be unique');
    }

    public function testValidatorUniqueAnalysesEndpointWithSpecialCharactersInIdentifier(): void
    {
        $client = self::createClient();

        // Get analysis types
        $analysisTypes = $this->getVocabulary(['analysis', 'types']);
        $this->assertNotEmpty($analysisTypes, 'Should have at least one analysis type for testing');

        $typeId = basename($analysisTypes[0]['id']);

        // Test with identifier containing special characters that should be URL encoded
        $identifierWithSpaces = 'TEST ANALYSIS 2025';
        $identifierWithDots = 'TEST.ANALYSIS.2025';
        $identifierWithSlashes = 'TEST/ANALYSIS/2025';

        // Test identifier with spaces
        $response1 = $this->apiRequest($client, 'GET', "/api/validator/unique/analyses?type={$typeId}&identifier=".urlencode($identifierWithSpaces));
        $this->assertSame(200, $response1->getStatusCode());
        $responseData1 = $response1->toArray();
        $this->assertArrayHasKey('valid', $responseData1);
        $this->assertSame(1, $responseData1['valid'], 'Identifier with spaces should be unique when not existing');

        // Test identifier with dots
        $response2 = $this->apiRequest($client, 'GET', "/api/validator/unique/analyses?type={$typeId}&identifier=".urlencode($identifierWithDots));
        $this->assertSame(200, $response2->getStatusCode());
        $responseData2 = $response2->toArray();
        $this->assertArrayHasKey('valid', $responseData2);
        $this->assertSame(1, $responseData2['valid'], 'Identifier with dots should be unique when not existing');

        // Test identifier with slashes
        $response3 = $this->apiRequest($client, 'GET', "/api/validator/unique/analyses?type={$typeId}&identifier=".urlencode($identifierWithSlashes));
        $this->assertSame(200, $response3->getStatusCode());
        $responseData3 = $response3->toArray();
        $this->assertArrayHasKey('valid', $responseData3);
        $this->assertSame(1, $responseData3['valid'], 'Identifier with slashes should be unique when not existing');
    }

    public function testValidatorUniqueAnalysesEndpointWithSameIdentifierDifferentType(): void
    {
        $client = self::createClient();

        // Get two different analysis types
        $analysisTypes = $this->getVocabulary(['analysis', 'types']);

        $type1Id = $analysisTypes[0]['id'];
        $type2Id = $analysisTypes[1]['id'];

        // Use the same identifier for both types - both should be unique since combination doesn't exist
        $identifier = 'SAME_IDENTIFIER_'.uniqid();

        // Test first type-identifier combination
        $response1 = $this->apiRequest($client, 'GET', "/api/validator/unique/analyses?type={$type1Id}&identifier=".urlencode($identifier));
        $this->assertSame(200, $response1->getStatusCode());
        $responseData1 = $response1->toArray();
        $this->assertArrayHasKey('valid', $responseData1);
        $this->assertSame(1, $responseData1['valid'], 'First type-identifier combination should be unique');

        // Test second type-identifier combination
        $response2 = $this->apiRequest($client, 'GET', "/api/validator/unique/analyses?type={$type2Id}&identifier=".urlencode($identifier));
        $this->assertSame(200, $response2->getStatusCode());
        $responseData2 = $response2->toArray();
        $this->assertArrayHasKey('valid', $responseData2);
        $this->assertSame(1, $responseData2['valid'], 'Second type-identifier combination should be unique');
    }

    public function testValidatorUniqueAnalysesEndpointWithEmptyIdentifier(): void
    {
        $client = self::createClient();

        // Get analysis types
        $analysisTypes = $this->getVocabulary(['analysis', 'types']);
        $this->assertNotEmpty($analysisTypes, 'Should have at least one analysis type for testing');

        $typeId = $analysisTypes[0]['id'];

        // Test with empty identifier - should be handled properly
        $response = $this->apiRequest($client, 'GET', "/api/validator/unique/analyses?type={$typeId}&identifier=");
        $this->assertSame(200, $response->getStatusCode());
        $responseData = $response->toArray();
        $this->assertArrayHasKey('valid', $responseData);
        // Should return 1 since empty identifier likely doesn't exist in combination with the type
        $this->assertSame(1, $responseData['valid'], 'Empty identifier should be unique when not existing');
    }

    /**
     * Get analyses data for testing.
     */
    private function getAnalyses(): array
    {
        $client = self::createClient();
        $token = $this->getUserToken($client, 'user_admin');

        $response = $this->apiRequest($client, 'GET', '/api/data/analyses', [
            'token' => $token,
        ]);

        $this->assertSame(200, $response->getStatusCode());

        $data = $response->toArray();

        return $data['member'] ?? [];
    }

    /**
     * Get stratigraphic unit relationships data for testing.
     */
    private function getStratigraphicUnitRelationships(): array
    {
        $client = self::createClient();
        $token = $this->getUserToken($client, 'user_admin');

        $response = $this->apiRequest($client, 'GET', '/api/data/stratigraphic_unit_relationships', [
            'token' => $token,
        ]);

        $this->assertSame(200, $response->getStatusCode());

        $data = $response->toArray();

        return $data['member'] ?? [];
    }
}
