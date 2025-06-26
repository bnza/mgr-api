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

    // PATCH Operation Tests

    public function testPatchSiteUserPrivilegeIsDeniedForAnonymousUser(): void
    {
        $client = self::createClient();

        // Get an existing privilege to update
        $privileges = $this->getSiteUserPrivileges();
        $privilegeId = $privileges[0]['@id'];

        $updateData = [
            'privilege' => 2,
        ];

        $response = $this->apiRequest($client, 'PATCH', $privilegeId, [
            'json' => $updateData,
        ]);

        $this->assertSame(401, $response->getStatusCode());
    }

    #[DataProvider('nonEditorUserProvider')]
    public function testPatchSiteUserPrivilegeIsDeniedForNonEditorUser(string $username): void
    {
        $client = self::createClient();

        // Login as non-editor user
        $loginResponse = $this->apiRequest($client, 'POST', '/api/login', [
            'json' => [
                'email' => "$username@example.com",
                'password' => $this->parameterBag->get("app.alice.parameters.{$username}_pw"),
            ],
        ]);

        $token = $loginResponse->toArray()['token'];

        // Get an existing privilege to update
        $privileges = $this->getSiteUserPrivileges();
        $privilegeId = $privileges[0]['@id'];

        $updateData = [
            'privilege' => 2,
        ];

        $response = $this->apiRequest($client, 'PATCH', $privilegeId, [
            'token' => $token,
            'json' => $updateData,
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
        ]);

        $this->assertSame(403, $response->getStatusCode());
    }

    public function testPatchSiteUserPrivilegeIsDeniedForEditorNonCreatorUser(): void
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

        // Find a privilege for a site not created by this editor
        $privileges = $this->getSiteUserPrivileges();
        $targetPrivilege = null;

        foreach ($privileges as $privilege) {
            $siteResponse = $this->apiRequest($client, 'GET', $privilege['site']['@id'], ['token' => $token]);
            if ($siteResponse->getStatusCode() === 200) {
                $siteData = $siteResponse->toArray();
                if ($siteData['createdBy']['userIdentifier'] !== 'user_editor@example.com') {
                    $targetPrivilege = $privilege;
                    break;
                }
            }
        }

        if (!$targetPrivilege) {
            $this->markTestSkipped('No privilege found for site not created by editor user');
        }

        $updateData = [
            'privilege' => 2,
        ];

        $response = $this->apiRequest($client, 'PATCH', $targetPrivilege['@id'], [
            'token' => $token,
            'json' => $updateData,
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
        ]);

        $this->assertSame(403, $response->getStatusCode());
    }

    public function testPatchSiteUserPrivilegeIsAllowedForEditorCreatorUser(): void
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

        // First create a privilege to update
        $targetUserIri = $this->getUserIri('user_base@example.com');
        $targetSiteIri = $this->getSiteIri('ME');

        $createData = [
            'user' => $targetUserIri,
            'site' => $targetSiteIri,
            'privilege' => 1,
        ];

        $createResponse = $this->apiRequest($client, 'POST', '/api/site_user_privileges', [
            'token' => $token,
            'json' => $createData,
        ]);

        $this->assertSame(201, $createResponse->getStatusCode());
        $createdPrivilege = $createResponse->toArray();

        // Now update the privilege
        $updateData = [
            'privilege' => 2,
        ];

        $response = $this->apiRequest($client, 'PATCH', $createdPrivilege['@id'], [
            'token' => $token,
            'json' => $updateData,
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
        ]);

        $this->assertSame(200, $response->getStatusCode());
        $updatedPrivilege = $response->toArray();
        $this->assertSame(2, $updatedPrivilege['privilege']);
    }

    public function testPatchSiteUserPrivilegeIsAllowedForAdminUser(): void
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

        // Get any existing privilege to update
        $privileges = $this->getSiteUserPrivileges();
        $privilegeId = $privileges[0]['@id'];

        $updateData = [
            'privilege' => 3,
        ];

        $response = $this->apiRequest($client, 'PATCH', $privilegeId, [
            'token' => $token,
            'json' => $updateData,
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
        ]);

        $this->assertSame(200, $response->getStatusCode());
        $updatedPrivilege = $response->toArray();
        $this->assertSame(3, $updatedPrivilege['privilege']);
    }

    public function testPatchSiteUserPrivilegeOnlyAllowsPrivilegeUpdate(): void
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

        // Get an existing privilege
        $privileges = $this->getSiteUserPrivileges();
        $originalPrivilege = $privileges[0];

        $attemptedUpdateData = [
            'user' => $this->getUserIri('user_base@example.com'),
            'site' => $this->getSiteIri('SE'),
            'privilege' => 5,
        ];

        $response = $this->apiRequest($client, 'PATCH', $originalPrivilege['@id'], [
            'token' => $token,
            'json' => $attemptedUpdateData,
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
        ]);

        $this->assertSame(200, $response->getStatusCode());
        $updatedPrivilege = $response->toArray();

        // Only privilege should be updated, user and site should remain the same
        $this->assertSame($originalPrivilege['user']['@id'], $updatedPrivilege['user']['@id']);
        $this->assertSame($originalPrivilege['site']['@id'], $updatedPrivilege['site']['@id']);
        $this->assertSame(5, $updatedPrivilege['privilege']);
    }

    public function testPatchSiteUserPrivilegeValidatesPrivilegeValue(): void
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

        // Get an existing privilege
        $privileges = $this->getSiteUserPrivileges();
        $privilegeId = $privileges[0]['@id'];

        // Test negative value
        $updateData = [
            'privilege' => -1,
        ];

        $response = $this->apiRequest($client, 'PATCH', $privilegeId, [
            'token' => $token,
            'json' => $updateData,
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
        ]);

        $this->assertSame(422, $response->getStatusCode());

        $violations = $response->toArray(false)['violations'];
        $this->assertGreaterThan(0, count($violations));
    }

    // DELETE Operation Tests

    public function testDeleteSiteUserPrivilegeIsDeniedForAnonymousUser(): void
    {
        $client = self::createClient();

        // Get an existing privilege to delete
        $privileges = $this->getSiteUserPrivileges();
        $privilegeId = $privileges[0]['@id'];

        $response = $this->apiRequest($client, 'DELETE', $privilegeId);

        $this->assertSame(401, $response->getStatusCode());
    }

    #[DataProvider('nonEditorUserProvider')]
    public function testDeleteSiteUserPrivilegeIsDeniedForNonEditorUser(string $username): void
    {
        $client = self::createClient();

        // Login as non-editor user
        $loginResponse = $this->apiRequest($client, 'POST', '/api/login', [
            'json' => [
                'email' => "$username@example.com",
                'password' => $this->parameterBag->get("app.alice.parameters.{$username}_pw"),
            ],
        ]);

        $token = $loginResponse->toArray()['token'];

        // Get an existing privilege to delete
        $privileges = $this->getSiteUserPrivileges();
        $privilegeId = $privileges[0]['@id'];

        $response = $this->apiRequest($client, 'DELETE', $privilegeId, [
            'token' => $token,
        ]);

        $this->assertSame(403, $response->getStatusCode());
    }

    public function testDeleteSiteUserPrivilegeIsNotFoundForEditorNonCreatorUser(): void
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

        // Find a privilege for a site not created by this editor
        $privileges = $this->getSiteUserPrivileges();
        $targetPrivilege = null;

        foreach ($privileges as $privilege) {
            $siteResponse = $this->apiRequest($client, 'GET', $privilege['site']['@id'], ['token' => $token]);
            if ($siteResponse->getStatusCode() === 200) {
                $siteData = $siteResponse->toArray();
                if ($siteData['createdBy']['userIdentifier'] !== 'user_editor@example.com') {
                    $targetPrivilege = $privilege;
                    break;
                }
            }
        }

        if (!$targetPrivilege) {
            $this->markTestSkipped('No privilege found for site not created by editor user');
        }

        $response = $this->apiRequest($client, 'DELETE', $targetPrivilege['@id'], [
            'token' => $token,
        ]);

        $this->assertSame(403, $response->getStatusCode());
    }

    public function testDeleteSiteUserPrivilegeIsAllowedForEditorCreatorUser(): void
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

        // First create a privilege to delete
        $targetUserIri = $this->getUserIri('user_base@example.com');
        $targetSiteIri = $this->getSiteIri('ME');

        $createData = [
            'user' => $targetUserIri,
            'site' => $targetSiteIri,
            'privilege' => 1,
        ];

        $createResponse = $this->apiRequest($client, 'POST', '/api/site_user_privileges', [
            'token' => $token,
            'json' => $createData,
        ]);

        $this->assertSame(201, $createResponse->getStatusCode());
        $createdPrivilege = $createResponse->toArray();

        // Now delete the privilege
        $response = $this->apiRequest($client, 'DELETE', $createdPrivilege['@id'], [
            'token' => $token,
        ]);

        $this->assertSame(204, $response->getStatusCode());

        // Verify it's deleted by trying to get it
        $getResponse = $this->apiRequest($client, 'GET', $createdPrivilege['@id'], [
            'token' => $token,
        ]);

        $this->assertSame(404, $getResponse->getStatusCode());
    }
}
