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

        $createResponse = $this->apiRequest($client, 'POST', '/api/stratigraphic_units', [
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
}
