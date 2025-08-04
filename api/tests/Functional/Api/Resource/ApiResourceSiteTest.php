<?php

namespace App\Tests\Functional\Api\Resource;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Tests\Functional\ApiTestRequestTrait;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class ApiResourceSiteTest extends ApiTestCase
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

    public function testFilterUnaccentedDescriptionGetCollection(): void
    {
        $client = self::createClient();

        $siteResponse = $this->apiRequest($client, 'GET', '/api/data/sites?description=balaghī'); // Matches "Balaghī" in description

        $this->assertSame(200, $siteResponse->getStatusCode());
        $siteData = $siteResponse->toArray();
        $this->assertCount(1, $siteData['member']);
        $this->assertSame('PA', $siteData['member'][0]['code']);

        $siteResponse = $this->apiRequest($client, 'GET', '/api/data/sites?description=balaghi'); // Matches "Balaghī" in description

        $this->assertSame(200, $siteResponse->getStatusCode());
        $siteData = $siteResponse->toArray();
        $this->assertCount(1, $siteData['member']);
        $this->assertSame('PA', $siteData['member'][0]['code']);
    }

    public function testFilterUnaccentedNameGetCollection(): void
    {
        $client = self::createClient();

        $siteResponse = $this->apiRequest($client, 'GET', '/api/data/sites?name=galmès'); // Matches "Galmès" in name

        $this->assertSame(200, $siteResponse->getStatusCode());
        $siteData = $siteResponse->toArray();
        $this->assertCount(1, $siteData['member']);
        $this->assertSame('TEG', $siteData['member'][0]['code']);

        $siteResponse = $this->apiRequest($client, 'GET', '/api/data/sites?name=galmes'); // Matches "Galmès" in name

        $this->assertSame(200, $siteResponse->getStatusCode());
        $siteData = $siteResponse->toArray();
        $this->assertCount(1, $siteData['member']);
        $this->assertSame('TEG', $siteData['member'][0]['code']);
    }

    public function testCreateUpdateSite(): void
    {
        $client = self::createClient();

        $token = $this->getUserToken($client, 'user_editor');

        $siteCode = $this->generateRandomSiteCode();
        $siteName = 'Test Site '.uniqid();
        $siteDescription = 'Test Site description '.uniqid();

        $siteResponse = $this->createTestSite(
            $client,
            $token,
            [
                'code' => $siteCode,
                'name' => $siteName,
                'description' => $siteDescription,
                'chronologyLower' => 1000,
                'chronologyUpper' => 1200,
                'fieldDirector' => 'Neil Lee',
            ]);

        $this->assertSame(201, $siteResponse->getStatusCode());
        $siteResponseData = $siteResponse->toArray();
        $this->assertSame($siteCode, $siteResponseData['code']);
        $this->assertSame($siteName, $siteResponseData['name']);
        $this->assertSame($siteDescription, $siteResponseData['description']);
        $this->assertSame(1000, $siteResponseData['chronologyLower']);
        $this->assertSame(1200, $siteResponseData['chronologyUpper']);
        $this->assertSame('Neil Lee', $siteResponseData['fieldDirector']);

        $siteResponse = $this->apiRequest($client, 'PATCH', $siteResponseData['@id'], [
            'token' => $token,
            'json' => [
                'description' => 'Updated description',
                'chronologyLower' => 1001,
                'chronologyUpper' => 1201,
                'fieldDirector' => 'Nils Bohr',
            ],
        ]);
        $siteResponseData = $siteResponse->toArray();
        $this->assertSame('Updated description', $siteResponseData['description']);
        $this->assertSame(1001, $siteResponseData['chronologyLower']);
        $this->assertSame(1201, $siteResponseData['chronologyUpper']);
        $this->assertSame('Nils Bohr', $siteResponseData['fieldDirector']);
    }

    public function testSearchFilterGetCollection(): void
    {
        $client = self::createClient();

        $token = $this->getUserToken($client, 'user_editor');

        $siteResponse = $this->createTestSite($client, $token, ['code' => 'ATA', 'name' => 'Test Site '.uniqid()]);

        $this->assertSame(201, $siteResponse->getStatusCode());

        $siteResponse = $this->apiRequest($client, 'GET', '/api/data/sites?search=at');

        $this->assertSame(200, $siteResponse->getStatusCode());
        $siteData = $siteResponse->toArray();
        $this->assertCount(1, $siteData['member']);
        $this->assertSame('ATA', $siteData['member'][0]['code']);

        $siteResponse = $this->apiRequest($client, 'GET', '/api/data/sites?search=ata');

        $this->assertSame(200, $siteResponse->getStatusCode());
        $siteData = $siteResponse->toArray();
        $this->assertCount(2, $siteData['member']);
        $this->assertSame('ATA', $siteData['member'][0]['code']);
        $this->assertSame('Pla d\'Almatà', $siteData['member'][1]['name']);

        $siteResponse = $this->apiRequest($client, 'GET', '/api/data/sites?search=atà');

        $this->assertSame(200, $siteResponse->getStatusCode());
        $siteData = $siteResponse->toArray();
        $this->assertCount(1, $siteData['member']);
        $this->assertSame('Pla d\'Almatà', $siteData['member'][0]['name']);
    }

    public function testSiteCreationGrantsEditorPrivilegeToCreator(): void
    {
        $client = self::createClient();

        $token = $this->getUserToken($client, 'user_editor');

        $siteResponse = $this->createTestSite($client, $token);

        $this->assertSame(201, $siteResponse->getStatusCode());
        $siteData = $siteResponse->toArray();
        $siteId = $siteData['id'];

        // Verify that site user privileges were created
        $privilegesResponse = $this->apiRequest($client, 'GET', '/api/admin/site_user_privileges', [
            'token' => $token,
        ]);

        $this->assertSame(200, $privilegesResponse->getStatusCode());
        $privileges = $privilegesResponse->toArray()['member'];

        // Find the privilege for the created site
        $sitePrivilege = null;
        foreach ($privileges as $privilege) {
            if ($privilege['site']['id'] === $siteId) {
                $sitePrivilege = $privilege;
                break;
            }
        }

        $this->assertNotNull($sitePrivilege, 'Site privilege should be created for the site creator');
        $this->assertSame(1, $sitePrivilege['privilege'], 'Creator should have Editor privilege (value 1)');
    }

    public function testSiteCreateCulturalContextsAreCreatedAndPatchedCorrectly(): void
    {
        $client = self::createClient();
        $token = $this->getUserToken($client, 'user_editor');

        $json = [
            'code' => 'NW',
            'name' => 'Test Site '.uniqid(),
            'description' => 'Test Site description',
            'culturalContexts' => [
                '/api/vocabulary/cultural_contexts/700',
                '/api/vocabulary/cultural_contexts/900',
            ],
        ];

        $siteResponse = $this->createTestSite($client, $token, $json);
        $this->assertSame(201, $siteResponse->getStatusCode());
        $siteData = $siteResponse->toArray();
        $this->assertArrayHasKey('culturalContexts', $siteData);
        $this->assertCount(2, $siteData['culturalContexts']);
        $this->assertSame('/api/vocabulary/cultural_contexts/700', $siteData['culturalContexts'][0]['@id']);
        $this->assertSame('/api/vocabulary/cultural_contexts/900', $siteData['culturalContexts'][1]['@id']);

        $siteResponse = $this->apiRequest($client, 'PATCH', $siteData['@id'], [
            'token' => $token,
            'json' => [
                'culturalContexts' => [
                    '/api/vocabulary/cultural_contexts/700',
                    '/api/vocabulary/cultural_contexts/800',
                    '/api/vocabulary/cultural_contexts/1000',
                ],
            ]]
        );
        $this->assertSame(200, $siteResponse->getStatusCode());
        $siteData = $siteResponse->toArray();
        $this->assertArrayHasKey('culturalContexts', $siteData);
        $this->assertCount(3, $siteData['culturalContexts']);
        $this->assertSame('/api/vocabulary/cultural_contexts/700', $siteData['culturalContexts'][0]['@id']);
        $this->assertSame('/api/vocabulary/cultural_contexts/800', $siteData['culturalContexts'][1]['@id']);
        $this->assertSame('/api/vocabulary/cultural_contexts/1000', $siteData['culturalContexts'][2]['@id']);
    }

    public function testEditorCanFetchCollection(): void
    {
        $client = self::createClient();

        $token = $this->getUserToken($client, 'user_editor');

        $siteResponse = $this->apiRequest($client, 'GET', '/api/data/sites', [
            'token' => $token,
        ]);

        $this->assertSame(200, $siteResponse->getStatusCode());
    }

    public function testAdminCanDeleteSite(): void
    {
        $client = self::createClient();

        $token = $this->getUserToken($client, 'user_admin');
        $siteResponse = $this->createTestSite($client, $token);
        $this->assertSame(201, $siteResponse->getStatusCode());
        $siteData = $siteResponse->toArray();
        $siteId = $siteData['id'];

        $response = $this->apiRequest($client, 'DELETE', "/api/data/sites/{$siteId}", [
            'token' => $token,
        ]);
        $this->assertSame(204, $response->getStatusCode());
    }

    public function testEditorCanDeleteSiteIfIsTheCreator(): void
    {
        $client = self::createClient();

        $token = $this->getUserToken($client, 'user_editor');
        $siteResponse = $this->createTestSite($client, $token);
        $this->assertSame(201, $siteResponse->getStatusCode());
        $siteData = $siteResponse->toArray();
        $siteId = $siteData['id'];

        $response = $this->apiRequest($client, 'DELETE', "/api/data/sites/{$siteId}", [
            'token' => $token,
        ]);
        $this->assertSame(204, $response->getStatusCode());
    }

    public function testEditorCannotDeleteSiteIfIsNotTheCreator(): void
    {
        $client = self::createClient();

        $token = $this->getUserToken($client, 'user_admin');

        $siteResponse = $this->createTestSite($client, $token);
        $this->assertSame(201, $siteResponse->getStatusCode());
        $siteData = $siteResponse->toArray();
        $siteId = $siteData['id'];

        $token = $this->getUserToken($client, 'user_editor');

        $response = $this->apiRequest($client, 'DELETE', "/api/data/sites/{$siteId}", [
            'token' => $token,
        ]);
        $this->assertSame(403, $response->getStatusCode());
    }

    public function testSiteCodeValidationLength(): void
    {
        $client = self::createClient();
        $token = $this->getUserToken($client, 'user_editor');

        // Test too short
        $response = $this->apiRequest($client, 'POST', '/api/data/sites', [
            'token' => $token,
            'json' => [
                'code' => 'A',
                'name' => 'Test Site',
            ],
        ]);

        $this->assertSame(422, $response->getStatusCode());
        $data = $response->toArray(false);
        $this->assertArrayHasKey('violations', $data);
        $this->assertGreaterThan(0, count($data['violations']));

        $codeViolation = array_filter($data['violations'], fn ($violation) => 'code' === $violation['propertyPath']);
        $this->assertNotEmpty($codeViolation);

        // Test too long
        $response = $this->apiRequest($client, 'POST', '/api/data/sites', [
            'token' => $token,
            'json' => [
                'code' => 'ABCDEFG',
                'name' => 'Test Site',
            ],
        ]);

        $this->assertSame(422, $response->getStatusCode());
        $data = $response->toArray(false);
        $this->assertArrayHasKey('violations', $data);
        $this->assertGreaterThan(0, count($data['violations']));

        $codeViolation = array_filter($data['violations'], fn ($violation) => 'code' === $violation['propertyPath']);
        $this->assertNotEmpty($codeViolation);
    }

    public function testSiteNameValidationBlank(): void
    {
        $client = self::createClient();
        $token = $this->getUserToken($client, 'user_editor');

        $response = $this->apiRequest($client, 'POST', '/api/data/sites', [
            'token' => $token,
            'json' => [
                'code' => 'TS',
                'name' => '',
            ],
        ]);

        $this->assertSame(422, $response->getStatusCode());
        $data = $response->toArray(false);
        $this->assertArrayHasKey('violations', $data);
        $this->assertGreaterThan(0, count($data['violations']));

        $nameViolation = array_filter($data['violations'], fn ($violation) => 'name' === $violation['propertyPath']);
        $this->assertNotEmpty($nameViolation);
    }

    public function testSiteUniqueCodeConstraint(): void
    {
        $client = self::createClient();
        $token = $this->getUserToken($client, 'user_editor');

        // Create first site
        $response = $this->apiRequest($client, 'POST', '/api/data/sites', [
            'token' => $token,
            'json' => [
                'code' => 'UC',
                'name' => 'Unique Code Test Site',
            ],
        ]);

        $this->assertSame(201, $response->getStatusCode());

        // Try to create another site with the same code
        $response = $this->apiRequest($client, 'POST', '/api/data/sites', [
            'token' => $token,
            'json' => [
                'code' => 'UC',
                'name' => 'Another Site',
            ],
        ]);

        $this->assertSame(422, $response->getStatusCode());
        $data = $response->toArray(false);
        $this->assertArrayHasKey('violations', $data);
        $this->assertGreaterThan(0, count($data['violations']));

        $codeViolation = array_filter($data['violations'], fn ($violation) => 'code' === $violation['propertyPath']);
        $this->assertNotEmpty($codeViolation);
    }

    public function testSiteUniqueNameConstraint(): void
    {
        $client = self::createClient();
        $token = $this->getUserToken($client, 'user_editor');

        $uniqueName = 'Unique Name Test Site '.uniqid();

        // Create first site
        $response = $this->apiRequest($client, 'POST', '/api/data/sites', [
            'token' => $token,
            'json' => [
                'code' => 'UN',
                'name' => $uniqueName,
            ],
        ]);

        $this->assertSame(201, $response->getStatusCode());

        // Try to create another site with the same name
        $response = $this->apiRequest($client, 'POST', '/api/data/sites', [
            'token' => $token,
            'json' => [
                'code' => 'U2',
                'name' => $uniqueName,
            ],
        ]);

        $this->assertSame(422, $response->getStatusCode());
        $data = $response->toArray(false);
        $this->assertArrayHasKey('violations', $data);
        $this->assertGreaterThan(0, count($data['violations']));

        $nameViolation = array_filter($data['violations'], fn ($violation) => 'name' === $violation['propertyPath']);
        $this->assertNotEmpty($nameViolation);
    }

    public function testChronologyLowerValidation(): void
    {
        $client = self::createClient();
        $token = $this->getUserToken($client, 'user_editor');

        // Test value too low (less than -32768)
        $response = $this->apiRequest($client, 'POST', '/api/data/sites', [
            'token' => $token,
            'json' => [
                'code' => 'CL1',
                'name' => 'Chronology Lower Test 1',
                'chronologyLower' => -32769,
            ],
        ]);

        $this->assertSame(422, $response->getStatusCode());
        $data = $response->toArray(false);
        $this->assertArrayHasKey('violations', $data);
        $this->assertGreaterThan(0, count($data['violations']));

        $chronologyViolation = array_filter($data['violations'], fn ($violation) => 'chronologyLower' === $violation['propertyPath']);
        $this->assertNotEmpty($chronologyViolation);

        // Test value greater than current year
        $currentYear = (int) date('Y');
        $futureYear = $currentYear + 1;

        $response = $this->apiRequest($client, 'POST', '/api/data/sites', [
            'token' => $token,
            'json' => [
                'code' => 'CL2',
                'name' => 'Chronology Lower Test 2',
                'chronologyLower' => $futureYear,
            ],
        ]);

        $this->assertSame(422, $response->getStatusCode());
        $data = $response->toArray(false);
        $this->assertArrayHasKey('violations', $data);
        $this->assertGreaterThan(0, count($data['violations']));

        $chronologyViolation = array_filter($data['violations'], fn ($violation) => 'chronologyLower' === $violation['propertyPath']);
        $this->assertNotEmpty($chronologyViolation);
    }

    public function testChronologyUpperValidation(): void
    {
        $client = self::createClient();
        $token = $this->getUserToken($client, 'user_editor');

        // Test value too low (less than -32768)
        $response = $this->apiRequest($client, 'POST', '/api/data/sites', [
            'token' => $token,
            'json' => [
                'code' => 'CU1',
                'name' => 'Chronology Upper Test 1',
                'chronologyUpper' => -32769,
            ],
        ]);

        $this->assertSame(422, $response->getStatusCode());
        $data = $response->toArray(false);
        $this->assertArrayHasKey('violations', $data);
        $this->assertGreaterThan(0, count($data['violations']));

        $chronologyViolation = array_filter($data['violations'], fn ($violation) => 'chronologyUpper' === $violation['propertyPath']);
        $this->assertNotEmpty($chronologyViolation);

        // Test value greater than current year
        $currentYear = (int) date('Y');
        $futureYear = $currentYear + 1;

        $response = $this->apiRequest($client, 'POST', '/api/data/sites', [
            'token' => $token,
            'json' => [
                'code' => 'CU2',
                'name' => 'Chronology Upper Test 2',
                'chronologyUpper' => $futureYear,
            ],
        ]);

        $this->assertSame(422, $response->getStatusCode());
        $data = $response->toArray(false);
        $this->assertArrayHasKey('violations', $data);
        $this->assertGreaterThan(0, count($data['violations']));

        $chronologyViolation = array_filter($data['violations'], fn ($violation) => 'chronologyUpper' === $violation['propertyPath']);
        $this->assertNotEmpty($chronologyViolation);
    }

    public function testChronologyRangeValidation(): void
    {
        $client = self::createClient();
        $token = $this->getUserToken($client, 'user_editor');

        // Test chronology upper less than chronology lower
        $response = $this->apiRequest($client, 'POST', '/api/data/sites', [
            'token' => $token,
            'json' => [
                'code' => 'CR1',
                'name' => 'Chronology Range Test 1',
                'chronologyLower' => 2010,
                'chronologyUpper' => 2000,
            ],
        ]);

        $this->assertSame(422, $response->getStatusCode());
        $data = $response->toArray(false);
        $this->assertArrayHasKey('violations', $data);
        $this->assertGreaterThan(0, count($data['violations']));

        $chronologyViolation = array_filter($data['violations'], fn ($violation) => 'chronologyUpper' === $violation['propertyPath']);
        $this->assertNotEmpty($chronologyViolation);
    }

    public function testSitePatchValidation(): void
    {
        $client = self::createClient();
        $token = $this->getUserToken($client, 'user_editor');

        // Create a site first
        $response = $this->apiRequest($client, 'POST', '/api/data/sites', [
            'token' => $token,
            'json' => [
                'code' => 'PT',
                'name' => 'Patch Test Site',
            ],
        ]);

        $this->assertSame(201, $response->getStatusCode());
        $siteData = $response->toArray();
        $siteId = $siteData['id'];

        // Test invalid code update
        $response = $this->apiRequest($client, 'PATCH', "/api/data/sites/{$siteId}", [
            'token' => $token,
            'json' => [
                'code' => 'invalid_code',
            ],
        ]);

        $this->assertSame(422, $response->getStatusCode());
        $data = $response->toArray(false);
        $this->assertArrayHasKey('violations', $data);
        $this->assertGreaterThan(0, count($data['violations']));

        $codeViolation = array_filter($data['violations'], fn ($violation) => 'code' === $violation['propertyPath']);
        $this->assertNotEmpty($codeViolation);

        // Test invalid chronology range update
        $response = $this->apiRequest($client, 'PATCH', "/api/data/sites/{$siteId}", [
            'token' => $token,
            'json' => [
                'chronologyLower' => 2010,
                'chronologyUpper' => 2000,
            ],
        ]);

        $this->assertSame(422, $response->getStatusCode());
        $data = $response->toArray(false);
        $this->assertArrayHasKey('violations', $data);
        $this->assertGreaterThan(0, count($data['violations']));

        $chronologyViolation = array_filter($data['violations'], fn ($violation) => 'chronologyUpper' === $violation['propertyPath']);
        $this->assertNotEmpty($chronologyViolation);
    }
}
