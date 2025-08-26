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

    public function testValidatorUniqueContextSampleEndpointReturnFalseWhenCombinationExists(): void
    {
        $client = self::createClient();

        // Get existing context-sample relationships
        $contextSamples = $this->getContextSamples();
        $this->assertNotEmpty($contextSamples, 'Should have at least one context-sample relationship for testing');

        $firstRelationship = $contextSamples[0];

        // Extract context ID and sample ID
        $contextId = basename($firstRelationship['context']);
        $sampleId = basename($firstRelationship['sample']);

        // Test existing context-sample combination - should return valid: false (0)
        $response = $this->apiRequest($client, 'GET', "/api/validator/unique/context_sample/{$contextId}/{$sampleId}");

        $this->assertSame(200, $response->getStatusCode());
        $responseData = $response->toArray();

        $this->assertArrayHasKey('valid', $responseData);
        $this->assertSame(0, $responseData['valid'], 'Existing context-sample combination should not be unique');
    }

    public function testValidatorUniqueContextSampleEndpointReturnTrueWhenCombinationNotExists(): void
    {
        $client = self::createClient();

        // Get contexts and samples to create a non-existing combination
        $contexts = $this->getContexts();
        $samples = $this->getSamples();

        $this->assertNotEmpty($contexts, 'Should have at least one context for testing');
        $this->assertNotEmpty($samples, 'Should have at least one sample for testing');

        // Use very high IDs that are unlikely to exist in combination
        $contextId = 999999;
        $sampleId = 999999;

        // Test non-existing context-sample combination - should return valid: true (1)
        $response = $this->apiRequest($client, 'GET', "/api/validator/unique/context_sample/{$contextId}/{$sampleId}");

        $this->assertSame(200, $response->getStatusCode());
        $responseData = $response->toArray();

        $this->assertArrayHasKey('valid', $responseData);
        $this->assertSame(1, $responseData['valid'], 'Non-existing context-sample combination should be unique');
    }

    public function testValidatorUniqueContextSampleEndpointWithInvalidContextId(): void
    {
        $client = self::createClient();

        // Get a valid sample ID
        $samples = $this->getSamples();
        $this->assertNotEmpty($samples, 'Should have at least one sample for testing');

        $validSampleId = $samples[0]['id'];
        $invalidContextId = 999999;

        // Test with invalid context ID - should return valid: true (1) since combination doesn't exist
        $response = $this->apiRequest($client, 'GET', "/api/validator/unique/context_sample/{$invalidContextId}/{$validSampleId}");

        $this->assertSame(200, $response->getStatusCode());
        $responseData = $response->toArray();

        $this->assertArrayHasKey('valid', $responseData);
        $this->assertSame(1, $responseData['valid'], 'Combination with invalid context ID should be unique');
    }

    public function testValidatorUniqueContextSampleEndpointWithInvalidSampleId(): void
    {
        $client = self::createClient();

        // Get a valid context ID
        $contexts = $this->getContexts();
        $this->assertNotEmpty($contexts, 'Should have at least one context for testing');

        $validContextId = $contexts[0]['id'];
        $invalidSampleId = 999999;

        // Test with invalid sample ID - should return valid: true (1) since combination doesn't exist
        $response = $this->apiRequest($client, 'GET', "/api/validator/unique/context_sample/{$validContextId}/{$invalidSampleId}");

        $this->assertSame(200, $response->getStatusCode());
        $responseData = $response->toArray();

        $this->assertArrayHasKey('valid', $responseData);
        $this->assertSame(1, $responseData['valid'], 'Combination with invalid sample ID should be unique');
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
        $potteryId = basename($firstPotteryAnalysis['pottery']['@id']);
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
}
