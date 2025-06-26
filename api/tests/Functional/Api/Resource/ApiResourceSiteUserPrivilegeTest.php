<?php

namespace App\Tests\Functional\Api\Resource;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Tests\Functional\ApiTestRequestTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class ApiResourceSiteUserPrivilegeTest extends ApiTestCase
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

    public function testGetCollectionReturnsOnlySiteCreatedByTheEditorUser(): void
    {
        $client = self::createClient();

        $loginResponse = $this->apiRequest($client, 'POST', '/api/login', [
            'json' => [
                'email' => 'user_editor@example.com',
                'password' => $this->parameterBag->get('app.alice.parameters.user_editor_pw'),
            ],
        ]);

        $this->assertSame(200, $loginResponse->getStatusCode());
        $token = $loginResponse->toArray()['token'];

        $privilegesResponse = $this->apiRequest($client, 'GET', '/api/site_user_privileges', [
            'token' => $token,
        ]);

        $this->assertSame(200, $privilegesResponse->getStatusCode());
        $privileges = $privilegesResponse->toArray()['member'];

        // Find the privilege for the created site
        foreach ($privileges as $privilege) {
            $siteResponse = $this->apiRequest($client, 'GET', $privilege['site']["@id"]);
            $siteData = $siteResponse->toArray();
            $this->assertSame('user_editor@example.com', $siteData['createdBy']['userIdentifier']);
        }
    }

    // POST Operation Tests

    public function testPostSiteUserPrivilegeIsDeniedForAnonymousUser(): void
    {
        $client = self::createClient();

        $users = $this->getUsers();
        $sites = $this->getSites();

        $privilegeData = [
            'user' => $users[0]['@id'],
            'site' => $sites[0]['@id'],
            'privilege' => 1,
        ];

        $response = $this->apiRequest($client, 'POST', '/api/site_user_privileges', [
            'json' => $privilegeData,
        ]);

        $this->assertSame(401, $response->getStatusCode());
    }

    public static function nonEditorUserProvider(): array
    {
        return [
            'user_base' => ['user_base'],
            'user_geo' => ['user_geo'],
        ];
    }

    #[DataProvider('nonEditorUserProvider')]
    public function testPostSiteUserPrivilegeIsDeniedForNonEditorUser(string $username): void
    {
        $client = self::createClient();

        // Login as non-editor user
        $loginResponse = $this->apiRequest($client, 'POST', '/api/login', [
            'json' => [
                'email' => "$username@example.com",
                'password' => $this->parameterBag->get("app.alice.parameters.{$username}_pw"),
            ],
        ]);

        $this->assertSame(200, $loginResponse->getStatusCode());
        $token = $loginResponse->toArray()['token'];

        $users = $this->getUsers();
        $sites = $this->getSites();

        $privilegeData = [
            'user' => $users[0]['@id'],
            'site' => $sites[0]['@id'],
            'privilege' => 1,
        ];

        $response = $this->apiRequest($client, 'POST', '/api/site_user_privileges', [
            'token' => $token,
            'json' => $privilegeData,
        ]);

        $this->assertSame(403, $response->getStatusCode());
    }

    public function testPostSiteUserPrivilegeIsDeniedForEditorNonCreatorUser(): void
    {
        $client = self::createClient();

        // Login as editor user
        $loginResponse = $this->apiRequest($client, 'POST', '/api/login', [
            'json' => [
                'email' => 'user_editor@example.com',
                'password' => $this->parameterBag->get('app.alice.parameters.user_editor_pw'),
            ],
        ]);

        $this->assertSame(200, $loginResponse->getStatusCode());
        $token = $loginResponse->toArray()['token'];

        $targetUserIri = $this->getUserIri('user_base@example.com');
        $targetSiteIri = $this->getSiteIri('CA');

        $privilegeData = [
            'user' => $targetUserIri,
            'site' => $targetSiteIri,
            'privilege' => 1,
        ];

        $response = $this->apiRequest($client, 'POST', '/api/site_user_privileges', [
            'token' => $token,
            'json' => $privilegeData,
        ]);
        $this->assertSame(403, $response->getStatusCode());
    }

    public function testPostSiteUserPrivilegeIsAllowedForEditorCreatorUser(): void
    {
        $client = self::createClient();

        // Login as editor user
        $loginResponse = $this->apiRequest($client, 'POST', '/api/login', [
            'json' => [
                'email' => 'user_editor@example.com',
                'password' => $this->parameterBag->get('app.alice.parameters.user_editor_pw'),
            ],
        ]);

        $this->assertSame(200, $loginResponse->getStatusCode());
        $token = $loginResponse->toArray()['token'];

        $targetUserIri = $this->getUserIri('user_base@example.com');
        $targetSiteIri = $this->getSiteIri('ME');

        $privilegeData = [
            'user' => $targetUserIri,
            'site' => $targetSiteIri,
            'privilege' => 1,
        ];

        $response = $this->apiRequest($client, 'POST', '/api/site_user_privileges', [
            'token' => $token,
            'json' => $privilegeData,
        ]);

        $this->assertSame(201, $response->getStatusCode());

        // Verify the privilege was created by checking the collection
        $privilegesResponse = $this->apiRequest($client, 'GET', '/api/site_user_privileges', [
            'token' => $token,
        ]);

        $this->assertSame(200, $privilegesResponse->getStatusCode());
        $privileges = $privilegesResponse->toArray()['member'];

        $found = false;
        foreach ($privileges as $privilege) {
            if ($privilege['user']['@id'] === $targetUserIri &&
                $privilege['site']['@id'] === $targetSiteIri &&
                $privilege['privilege'] === 1) {
                $found = true;
                break;
            }
        }
        $this->assertTrue($found, 'Created privilege should be found in the collection');
    }

    public function testPostSiteUserPrivilegeIsAllowedForAdminUser(): void
    {
        $client = self::createClient();

        // Login as admin user
        $loginResponse = $this->apiRequest($client, 'POST', '/api/login', [
            'json' => [
                'email' => 'user_admin@example.com',
                'password' => $this->parameterBag->get('app.alice.parameters.user_admin_pw'),
            ],
        ]);

        $this->assertSame(200, $loginResponse->getStatusCode());
        $token = $loginResponse->toArray()['token'];


        $targetUserIri = $this->getUserIri('user_base@example.com'); // Use different user than editor test
        $targetSiteIri = $this->getSiteIri('SE'); // Use different site than editor test

        $privilegeData = [
            'user' => $targetUserIri,
            'site' => $targetSiteIri,
            'privilege' => 3,
        ];

        $response = $this->apiRequest($client, 'POST', '/api/site_user_privileges', [
            'token' => $token,
            'json' => $privilegeData,
        ]);

        $this->assertSame(201, $response->getStatusCode());
    }

    public function testPostSiteUserPrivilegeValidatesRequiredFields(): void
    {
        $client = self::createClient();

        // Login as editor user
        $loginResponse = $this->apiRequest($client, 'POST', '/api/login', [
            'json' => [
                'email' => 'user_admin@example.com',
                'password' => $this->parameterBag->get('app.alice.parameters.user_admin_pw'),
            ],
        ]);

        $token = $loginResponse->toArray()['token'];

        // Test missing user
        $sites = $this->getSites();
        $response = $this->apiRequest($client, 'POST', '/api/site_user_privileges', [
            'token' => $token,
            'json' => [
                'site' => $sites[0]['@id'],
                'privilege' => 1,
            ],
        ]);

        $this->assertSame(422, $response->getStatusCode());

        // Test missing site
        $users = $this->getUsers();
        $response = $this->apiRequest($client, 'POST', '/api/site_user_privileges', [
            'token' => $token,
            'json' => [
                'user' => $users[0]['@id'],
                'privilege' => 1,
            ],
        ]);

        $this->assertSame(422, $response->getStatusCode());

        $targetUserIri = $this->getUserIri('user_base@example.com');
        $targetSiteIri = $this->getSiteIri('SE');

        // Test missing privilege (should default to 0)
        $response = $this->apiRequest($client, 'POST', '/api/site_user_privileges', [
            'token' => $token,
            'json' => [
                'user' => $targetUserIri,
                'site' => $targetSiteIri,
            ],
        ]);

        // This might succeed with default privilege value
        $this->assertSame(201, $response->getStatusCode());
    }

    public function testPostSiteUserPrivilegeValidatesUniqueness(): void
    {
        $client = self::createClient();

        // Login as editor user
        $loginResponse = $this->apiRequest($client, 'POST', '/api/login', [
            'json' => [
                'email' => 'user_admin@example.com',
                'password' => $this->parameterBag->get('app.alice.parameters.user_admin_pw'),
            ],
        ]);

        $token = $loginResponse->toArray()['token'];

        $targetUserIri = $this->getUserIri('user_base@example.com');
        $targetSiteIri = $this->getSiteIri('SE');

        $privilegeData = [
            'user' => $targetUserIri,
            'site' => $targetSiteIri,
            'privilege' => 1,
        ];

        // Create first privilege
        $response = $this->apiRequest($client, 'POST', '/api/site_user_privileges', [
            'token' => $token,
            'json' => $privilegeData,
        ]);

        $this->assertSame(201, $response->getStatusCode());

        // Try to create duplicate privilege
        $response = $this->apiRequest($client, 'POST', '/api/site_user_privileges', [
            'token' => $token,
            'json' => $privilegeData,
        ]);

        $this->assertSame(422, $response->getStatusCode());

        $violations = $response->toArray(false)['violations'];
        $this->assertGreaterThan(0, count($violations));

        // Check for uniqueness violation
        $uniquenessViolationFound = false;
        foreach ($violations as $violation) {
            if (str_contains($violation['message'], 'already has permissions')) {
                $uniquenessViolationFound = true;
                break;
            }
        }
        $this->assertTrue($uniquenessViolationFound, 'Uniqueness constraint violation should be found');
    }

    public function testPostSiteUserPrivilegeValidatesInvalidUserReference(): void
    {
        $client = self::createClient();

        // Login as editor user
        $loginResponse = $this->apiRequest($client, 'POST', '/api/login', [
            'json' => [
                'email' => 'user_editor@example.com',
                'password' => $this->parameterBag->get('app.alice.parameters.user_editor_pw'),
            ],
        ]);

        $token = $loginResponse->toArray()['token'];
        $sites = $this->getSites();

        $privilegeData = [
            'user' => '/api/users/nonexistent-uuid',
            'site' => $sites[0]['@id'],
            'privilege' => 1,
        ];

        $response = $this->apiRequest($client, 'POST', '/api/site_user_privileges', [
            'token' => $token,
            'json' => $privilegeData,
        ]);

        $this->assertSame(400, $response->getStatusCode());
    }

    public function testPostSiteUserPrivilegeValidatesInvalidSiteReference(): void
    {
        $client = self::createClient();

        // Login as editor user
        $loginResponse = $this->apiRequest($client, 'POST', '/api/login', [
            'json' => [
                'email' => 'user_editor@example.com',
                'password' => $this->parameterBag->get('app.alice.parameters.user_editor_pw'),
            ],
        ]);

        $token = $loginResponse->toArray()['token'];
        $users = $this->getUsers();

        $privilegeData = [
            'user' => $users[0]['@id'],
            'site' => '/api/sites/999999',
            'privilege' => 1,
        ];

        $response = $this->apiRequest($client, 'POST', '/api/site_user_privileges', [
            'token' => $token,
            'json' => $privilegeData,
        ]);

        $this->assertSame(400, $response->getStatusCode());
    }

    public static function invalidPrivilegeValueProvider(): array
    {
        return [
            'negative_value' => [-1],
            'string_value' => ['invalid'],
        ];
    }

    #[DataProvider('invalidPrivilegeValueProvider')]
    public function testPostSiteUserPrivilegeValidatesPrivilegeValue($invalidPrivilege): void
    {
        $client = self::createClient();

        // Login as editor user
        $loginResponse = $this->apiRequest($client, 'POST', '/api/login', [
            'json' => [
                'email' => 'user_admin@example.com',
                'password' => $this->parameterBag->get('app.alice.parameters.user_admin_pw'),
            ],
        ]);

        $token = $loginResponse->toArray()['token'];

        $targetUserIri = $this->getUserIri('user_base@example.com');
        $targetSiteIri = $this->getSiteIri('SE');

        $privilegeData = [
            'user' => $targetUserIri,
            'site' => $targetSiteIri,
            'privilege' => $invalidPrivilege,
        ];

        $response = $this->apiRequest($client, 'POST', '/api/site_user_privileges', [
            'token' => $token,
            'json' => $privilegeData,
        ]);

        // Should return 400 for invalid data types or values
        // @TODO use DTO to validate type
        $this->assertTrue(in_array($response->getStatusCode(), [400, 422]));
    }

    public function testPostSiteUserPrivilegeReturnsJsonLdResponse(): void
    {
        $client = self::createClient();

        // Login as admin user
        $loginResponse = $this->apiRequest($client, 'POST', '/api/login', [
            'json' => [
                'email' => 'user_admin@example.com',
                'password' => $this->parameterBag->get('app.alice.parameters.user_admin_pw'),
            ],
        ]);

        $token = $loginResponse->toArray()['token'];

        $targetUserIri = $this->getUserIri('user_base@example.com');
        $targetSiteIri = $this->getSiteIri('SE');

        $privilegeData = [
            'user' => $targetUserIri,
            'site' => $targetSiteIri,
            'privilege' => 1,
        ];

        $response = $this->apiRequest($client, 'POST', '/api/site_user_privileges', [
            'token' => $token,
            'json' => $privilegeData,
        ]);

        $this->assertSame(201, $response->getStatusCode());

        $responseData = $response->toArray();
        $this->assertArrayHasKey('@context', $responseData);
        $this->assertArrayHasKey('@id', $responseData);
        $this->assertArrayHasKey('@type', $responseData);
        $this->assertSame($privilegeData['user'], $responseData['user']['@id']);
        $this->assertSame($privilegeData['site'], $responseData['site']['@id']);
        $this->assertSame($privilegeData['privilege'], $responseData['privilege']);
    }
}
