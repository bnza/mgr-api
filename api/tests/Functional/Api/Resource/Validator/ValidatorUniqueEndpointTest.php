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

    public function testValidatorUniqueSiteCodeEndpointReturnFalseWhenCodeExists(): void
    {
        $client = self::createClient();

        // Test with an existing site code
        $sites = $this->getSites();
        $this->assertNotEmpty($sites, 'Should have at least one site for testing');

        $existingSiteCode = $sites[0]['code'];

        // Test existing code - should return unique: false
        $response = $this->apiRequest($client, 'GET', "/api/validator/unique/sites/code/{$existingSiteCode}");

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

        $response = $this->apiRequest($client, 'GET', "/api/validator/unique/sites/code/{$nonExistentCode}");

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
        $response = $this->apiRequest($client, 'GET', "/api/validator/unique/site_user_privileges/{$siteId}/{$userId}");

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
        $response = $this->apiRequest($client, 'GET', "/api/validator/unique/stratigraphic_units/$siteId/$year/$number");

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
        $response = $this->apiRequest($client, 'GET', "/api/validator/unique/stratigraphic_units/$siteId/$year/$number");

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
        $response = $this->apiRequest($client, 'GET', "/api/validator/unique/stratigraphic_units/$siteId/$year/$number");

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
        $response = $this->apiRequest($client, 'GET', "/api/validator/unique/context_stratigraphic_units/{$contextId}/{$stratigraphicUnitId}");

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
        $response = $this->apiRequest($client, 'GET', "/api/validator/unique/context_stratigraphic_units/{$contextId}/{$stratigraphicUnitId}");

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
        $response = $this->apiRequest($client, 'GET', "/api/validator/unique/context_stratigraphic_units/{$invalidContextId}/{$validStratigraphicUnitId}");

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
        $response = $this->apiRequest($client, 'GET', "/api/validator/unique/context_stratigraphic_units/{$validContextId}/{$invalidStratigraphicUnitId}");

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
        $response = $this->apiRequest($client, 'GET', "/api/validator/unique/sample_stratigraphic_units/{$sampleId}/{$stratigraphicUnitId}");

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
        $response = $this->apiRequest($client, 'GET', "/api/validator/unique/sample_stratigraphic_units/{$sampleId}/{$stratigraphicUnitId}");

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
        $response = $this->apiRequest($client, 'GET', "/api/validator/unique/sample_stratigraphic_units/{$invalidSampleId}/{$validStratigraphicUnitId}");

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
        $response = $this->apiRequest($client, 'GET', "/api/validator/unique/sample_stratigraphic_units/{$validSampleId}/{$invalidStratigraphicUnitId}");

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
        $response = $this->apiRequest($client, 'GET', "/api/validator/unique/samples/{$siteId}/{$typeId}/{$year}/{$number}");

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
        $response = $this->apiRequest($client, 'GET', "/api/validator/unique/samples/{$siteId}/{$typeId}/{$year}/{$number}");

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
        $response = $this->apiRequest($client, 'GET', "/api/validator/unique/samples/{$invalidSiteId}/{$validTypeId}/{$year}/{$number}");

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
        $response = $this->apiRequest($client, 'GET', "/api/validator/unique/samples/{$validSiteId}/{$invalidTypeId}/{$year}/{$number}");

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
        $response = $this->apiRequest($client, 'GET', "/api/validator/unique/potteries/inventory/{$existingInventory}");

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

        $response = $this->apiRequest($client, 'GET', "/api/validator/unique/potteries/inventory/{$nonExistentInventory}");

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
        $potteryId = basename($firstPotteryAnalysis['item']['@id']);
        $typeId = basename($firstPotteryAnalysis['type']);

        // Test existing pottery-analysis type combination - should return valid: false (0)
        $response = $this->apiRequest($client, 'GET', "/api/validator/unique/analyses/potteries/{$potteryId}/{$typeId}");

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
        $response = $this->apiRequest($client, 'GET', "/api/validator/unique/analyses/potteries/{$potteryId}/{$typeId}");

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
        $response = $this->apiRequest($client, 'GET', "/api/validator/unique/media_objects/stratigraphic_units/{$mediaObjectId}/{$stratigraphicUnitId}");

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
        $response = $this->apiRequest($client, 'GET', "/api/validator/unique/media_objects/stratigraphic_units/{$mediaObjectId}/{$stratigraphicUnitId}");

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
        $response = $this->apiRequest($client, 'GET', "/api/validator/unique/stratigraphic_unit_relationships/{$lftSuId}/{$rgtSuId}");

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
        $response = $this->apiRequest($client, 'GET', "/api/validator/unique/stratigraphic_unit_relationships/{$lftSuId}/{$rgtSuId}");

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
        $response = $this->apiRequest($client, 'GET', "/api/validator/unique/stratigraphic_unit_relationships/{$invalidLftSuId}/{$validRgtSuId}");

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
        $response = $this->apiRequest($client, 'GET', "/api/validator/unique/stratigraphic_unit_relationships/{$validLftSuId}/{$invalidRgtSuId}");

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
        $response = $this->apiRequest($client, 'GET', "/api/validator/unique/analyses/{$typeId}/{$identifier}");

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
        $response = $this->apiRequest($client, 'GET', "/api/validator/unique/analyses/{$typeId}/{$nonExistentIdentifier}");

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
        $response = $this->apiRequest($client, 'GET', "/api/validator/unique/analyses/{$invalidTypeId}/{$identifier}");

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
        $response1 = $this->apiRequest($client, 'GET', "/api/validator/unique/analyses/{$typeId}/".urlencode($identifierWithSpaces));
        $this->assertSame(200, $response1->getStatusCode());
        $responseData1 = $response1->toArray();
        $this->assertArrayHasKey('valid', $responseData1);
        $this->assertSame(1, $responseData1['valid'], 'Identifier with spaces should be unique when not existing');

        // Test identifier with dots
        $response2 = $this->apiRequest($client, 'GET', "/api/validator/unique/analyses/{$typeId}/".urlencode($identifierWithDots));
        $this->assertSame(200, $response2->getStatusCode());
        $responseData2 = $response2->toArray();
        $this->assertArrayHasKey('valid', $responseData2);
        $this->assertSame(1, $responseData2['valid'], 'Identifier with dots should be unique when not existing');

        // Test identifier with slashes
        $response3 = $this->apiRequest($client, 'GET', "/api/validator/unique/analyses/{$typeId}/".urlencode($identifierWithSlashes));
        $this->assertSame(200, $response3->getStatusCode());
        $responseData3 = $response3->toArray();
        $this->assertArrayHasKey('valid', $responseData3);
        $this->assertSame(1, $responseData3['valid'], 'Identifier with slashes should be unique when not existing');
    }

    //    public function testValidatorUniqueAnalysesEndpointWithExistingIdentifiersFromFixtures(): void
    //    {
    //        $client = self::createClient();
    //
    //        // Test with specific fixtures data from data.analysis.yaml
    //
    //        // XRF analysis with identifier 'XRFAN.2025.A1'
    //        $xrfType = $this->getAnalysisTypeByCode('XRF');
    //        $this->assertNotNull($xrfType, 'Should have XRF analysis type');
    //
    //        $response = $this->apiRequest($client, 'GET', "/api/validator/unique/analyses/{$xrfType['id']}/XRFAN.2025.A1");
    //        $this->assertSame(200, $response->getStatusCode());
    //        $responseData = $response->toArray();
    //        $this->assertArrayHasKey('valid', $responseData);
    //        $this->assertSame(0, $responseData['valid'], 'XRF analysis with existing identifier should not be unique');
    //
    //        // SEM analysis with identifier 'microSEM.25.ME 110'
    //        $semType = $this->getAnalysisTypeByCode('SEM');
    //        $this->assertNotNull($semType, 'Should have SEM analysis type');
    //
    //        $response = $this->apiRequest($client, 'GET', "/api/validator/unique/analyses/{$semType['id']}/" . urlencode('microSEM.25.ME 110'));
    //        $this->assertSame(200, $response->getStatusCode());
    //        $responseData = $response->toArray();
    //        $this->assertArrayHasKey('valid', $responseData);
    //        $this->assertSame(0, $responseData['valid'], 'SEM analysis with existing identifier should not be unique');
    //
    //        // ADNA analysis with identifier 'aDNA.2025.ME102'
    //        $adnaType = $this->getAnalysisTypeByCode('ADNA');
    //        $this->assertNotNull($adnaType, 'Should have ADNA analysis type');
    //
    //        $response = $this->apiRequest($client, 'GET', "/api/validator/unique/analyses/{$adnaType['id']}/aDNA.2025.ME102");
    //        $this->assertSame(200, $response->getStatusCode());
    //        $responseData = $response->toArray();
    //        $this->assertArrayHasKey('valid', $responseData);
    //        $this->assertSame(0, $responseData['valid'], 'ADNA analysis with existing identifier should not be unique');
    //    }

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
        $response1 = $this->apiRequest($client, 'GET', "/api/validator/unique/analyses/{$type1Id}/{$identifier}");
        $this->assertSame(200, $response1->getStatusCode());
        $responseData1 = $response1->toArray();
        $this->assertArrayHasKey('valid', $responseData1);
        $this->assertSame(1, $responseData1['valid'], 'First type-identifier combination should be unique');

        // Test second type-identifier combination
        $response2 = $this->apiRequest($client, 'GET', "/api/validator/unique/analyses/{$type2Id}/{$identifier}");
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
        $response = $this->apiRequest($client, 'GET', "/api/validator/unique/analyses/{$typeId}/");
        $this->assertSame(404, $response->getStatusCode());
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
