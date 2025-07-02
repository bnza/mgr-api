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

    public function testSiteCreationGrantsEditorPrivilegeToCreator(): void
    {
        $client = self::createClient();

        $token = $this->getUserToken($client, 'user_editor');

        $siteResponse = $this->apiRequest($client, 'POST', '/api/sites', [
            'token' => $token,
            'json' => [
                'code' => 'test-site-'.uniqid(),
                'name' => 'Test Site '.uniqid(),
                'description' => 'Test site for privilege testing',
            ],
        ]);

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
}
