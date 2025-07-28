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

        $siteResponse = $this->apiRequest($client, 'GET', '/api/sites?description=balaghī'); // Matches "Balaghī" in description

        $this->assertSame(200, $siteResponse->getStatusCode());
        $siteData = $siteResponse->toArray();
        $this->assertCount(1, $siteData['member']);
        $this->assertSame('PA', $siteData['member'][0]['code']);

        $siteResponse = $this->apiRequest($client, 'GET', '/api/sites?description=balaghi'); // Matches "Balaghī" in description

        $this->assertSame(200, $siteResponse->getStatusCode());
        $siteData = $siteResponse->toArray();
        $this->assertCount(1, $siteData['member']);
        $this->assertSame('PA', $siteData['member'][0]['code']);
    }

    public function testFilterUnaccentedNameGetCollection(): void
    {
        $client = self::createClient();

        $siteResponse = $this->apiRequest($client, 'GET', '/api/sites?name=galmès'); // Matches "Galmès" in name

        $this->assertSame(200, $siteResponse->getStatusCode());
        $siteData = $siteResponse->toArray();
        $this->assertCount(1, $siteData['member']);
        $this->assertSame('TEG', $siteData['member'][0]['code']);

        $siteResponse = $this->apiRequest($client, 'GET', '/api/sites?name=galmes'); // Matches "Galmès" in name

        $this->assertSame(200, $siteResponse->getStatusCode());
        $siteData = $siteResponse->toArray();
        $this->assertCount(1, $siteData['member']);
        $this->assertSame('TEG', $siteData['member'][0]['code']);
    }

    public function testSearchFilterGetCollection(): void
    {
        $client = self::createClient();

        $token = $this->getUserToken($client, 'user_editor');

        $siteResponse = $this->createTestSite($client, $token, ['code' => 'ATA', 'name' => 'Test Site '.uniqid()]);

        $this->assertSame(201, $siteResponse->getStatusCode());

        $siteResponse = $this->apiRequest($client, 'GET', '/api/sites?search=at');

        $this->assertSame(200, $siteResponse->getStatusCode());
        $siteData = $siteResponse->toArray();
        $this->assertCount(1, $siteData['member']);
        $this->assertSame('ATA', $siteData['member'][0]['code']);

        $siteResponse = $this->apiRequest($client, 'GET', '/api/sites?search=ata');

        $this->assertSame(200, $siteResponse->getStatusCode());
        $siteData = $siteResponse->toArray();
        $this->assertCount(2, $siteData['member']);
        $this->assertSame('ATA', $siteData['member'][0]['code']);
        $this->assertSame('Pla d\'Almatà', $siteData['member'][1]['name']);

        $siteResponse = $this->apiRequest($client, 'GET', '/api/sites?search=atà');

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
        $privilegesResponse = $this->apiRequest($client, 'GET', '/api/site_user_privileges', [
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
        $this->assertSame(1, $sitePrivilege['privilege'], 'Creator should have Editor privilege (value 2)');
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

        $siteResponse = $this->apiRequest($client, 'GET', '/api/sites', [
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

        $response = $this->apiRequest($client, 'DELETE', "/api/sites/{$siteId}", [
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

        $response = $this->apiRequest($client, 'DELETE', "/api/sites/{$siteId}", [
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

        $response = $this->apiRequest($client, 'DELETE', "/api/sites/{$siteId}", [
            'token' => $token,
        ]);
        $this->assertSame(403, $response->getStatusCode());
    }
}
