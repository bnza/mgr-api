<?php

namespace App\Tests\Functional\Api\Resource;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class ApiResourceSiteUserPrivilegeTest extends ApiTestCase
{
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

    public function testGetCollectionReturnsOnlySiteCreatedByTheEditorUser(): void
    {
        $client = self::createClient();

        $loginResponse = $client->request('POST', '/api/login', [
            'json' => [
                'email' => 'user_editor@example.com',
                'password' => $this->parameterBag->get('app.alice.parameters.user_editor_pw'),
            ],
        ]);

        $this->assertSame(200, $loginResponse->getStatusCode());
        $token = $loginResponse->toArray()['token'];

        $privilegesResponse = $client->request('GET', '/api/site_user_privileges', [
            'headers' => [
                'Authorization' => "Bearer $token",
            ],
        ]);


        $this->assertSame(200, $privilegesResponse->getStatusCode());
        $privileges = $privilegesResponse->toArray()['member'];

        // Find the privilege for the created site
        foreach ($privileges as $privilege) {
            $siteResponse = $client->request('GET', $privilege['site']["@id"]);
            $siteData = $siteResponse->toArray();
            $this->assertSame('user_editor@example.com', $siteData['createdBy']['userIdentifier']);
        }
    }
}
