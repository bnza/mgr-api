<?php

namespace App\Tests\Functional\Api\Resource;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Tests\Functional\Api\ApiTestProviderTrait;
use App\Tests\Functional\ApiTestRequestTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class ApiResourceUserTest extends ApiTestCase
{
    use ApiTestRequestTrait;
    use ApiTestProviderTrait;

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

    public function testGetCollectionIsDeniedForAnonymousUser()
    {
        $client = self::createClient();
        $users = $this->getUsers();
        $loginResponse = $this->apiRequest($client, 'GET', $users[0]['@id']);
        $this->assertSame(401, $loginResponse->getStatusCode());
    }

    public function testGetItemIsDeniedForAnonymousUser()
    {
        $client = self::createClient();
        $loginResponse = $this->apiRequest($client, 'GET', '/api/users');
        $this->assertSame(401, $loginResponse->getStatusCode());
    }

    #[DataProvider('nonAdminUserProvider')]
    public function testGetCollectionIsDeniedForNonAdminUser(string $username): void
    {
        $client = self::createClient();

        $token = $this->getUserToken($client, $username);

        $userResponse = $this->apiRequest($client, 'GET', '/api/users', [
            'token' => $token,
        ]);

        $this->assertSame(403, $userResponse->getStatusCode());
    }

    #[DataProvider('nonAdminUserProvider')]
    public function testGetItemIsDeniedForNonAdminUser(string $username): void
    {
        $client = self::createClient();

        $token = $this->getUserToken($client, $username);

        $users = $this->getUsers();

        $userResponse = $this->apiRequest($client, 'GET', $users[0]['@id'], [
            'token' => $token,
        ]);

        $this->assertSame(403, $userResponse->getStatusCode());
    }

    public function testGetCollectionIsAllowedForAdminUser()
    {
        $this->getUsers();
    }

    // POST Operation Tests

    public function testPostUserIsDeniedForAnonymousUser(): void
    {
        $client = self::createClient();

        $userData = [
            'email' => 'newuser@example.com',
            'plainPassword' => 'StrongPass123',
            'roles' => ['ROLE_USER'],
        ];

        $response = $this->apiRequest($client, 'POST', '/api/users', [
            'json' => $userData,
        ]);

        $this->assertSame(401, $response->getStatusCode());
    }

    #[DataProvider('nonAdminUserProvider')]
    public function testPostUserIsDeniedForNonAdminUser(string $username): void
    {
        $client = self::createClient();

        $token = $this->getUserToken($client, $username);

        $userData = [
            'email' => 'newuser@example.com',
            'plainPassword' => 'StrongPass123',
            'roles' => ['ROLE_USER'],
        ];

        $response = $this->apiRequest($client, 'POST', '/api/users', [
            'token' => $token,
            'json' => $userData,
        ]);

        $this->assertSame(403, $response->getStatusCode());
    }

    public function testPostUserIsAllowedForAdminUser(): void
    {
        $client = self::createClient();

        $token = $this->getUserToken($client, 'user_admin');

        $userData = [
            'email' => 'newuser@example.com',
            'plainPassword' => 'StrongPass123!',
            'roles' => ['ROLE_USER'],
        ];

        $response = $this->apiRequest($client, 'POST', '/api/users', [
            'token' => $token,
            'json' => $userData,
        ]);

        $this->assertSame(201, $response->getStatusCode());

        $responseData = $response->toArray();
        $this->assertSame('newuser@example.com', $responseData['email']);
        $this->assertContains('ROLE_USER', $responseData['roles']);
    }

    public static function invalidEmailProvider(): array
    {
        return [
            'empty_email' => [''],
            'invalid_format' => ['invalid-email'],
            'no_domain' => ['user@'],
            'no_username' => ['@example.com'],
        ];
    }

    #[DataProvider('invalidEmailProvider')]
    public function testPostUserValidatesEmailFormat(string $invalidEmail): void
    {
        $client = self::createClient();

        $token = $this->getUserToken($client, 'user_admin');

        $userData = [
            'email' => $invalidEmail,
            'plainPassword' => 'StrongPass123',
            'roles' => ['ROLE_USER'],
        ];

        $response = $this->apiRequest($client, 'POST', '/api/users', [
            'token' => $token,
            'json' => $userData,
        ]);

        $this->assertSame(422, $response->getStatusCode());
        $violations = $response->toArray(false)['violations'];
        $this->assertGreaterThan(0, count($violations));
    }

    public static function invalidPasswordProvider(): array
    {
        return [
            'empty_password' => [''],
            'too_short' => ['Short1'],
            'no_uppercase' => ['lowercase123!'],
            'no_lowercase' => ['UPPERCASE123!'],
            'no_digit' => ['NoDigitPassword!'],
            'no_special_char' => ['NoSpecial123'],
            'too_long' => ['ThisPasswordIsTooLongAndShouldFailValidation123'],
        ];
    }

    #[DataProvider('invalidPasswordProvider')]
    public function testPostUserValidatesPasswordStrength(string $invalidPassword): void
    {
        $client = self::createClient();

        $token = $this->getUserToken($client, 'user_admin');

        $userData = [
            'email' => 'validuser@example.com',
            'plainPassword' => $invalidPassword,
            'roles' => ['ROLE_USER'],
        ];

        $response = $this->apiRequest($client, 'POST', '/api/users', [
            'token' => $token,
            'json' => $userData,
        ]);

        $this->assertSame(422, $response->getStatusCode());
        $violations = $response->toArray(false)['violations'];
        $this->assertGreaterThan(0, count($violations));
    }

    public static function invalidRolesProvider(): array
    {
        return [
            'empty_roles' => [[]],
            'invalid_role' => [['ROLE_INVALID']],
            'mixed_valid_invalid' => [['ROLE_USER', 'ROLE_INVALID']],
            'empty_role_string' => [['']],
        ];
    }

    #[DataProvider('invalidRolesProvider')]
    public function testPostUserValidatesRoles(array $invalidRoles): void
    {
        $client = self::createClient();

        $token = $this->getUserToken($client, 'user_admin');

        $userData = [
            'email' => 'validuser@example.com',
            'plainPassword' => 'StrongPass123',
            'roles' => $invalidRoles,
        ];

        $response = $this->apiRequest($client, 'POST', '/api/users', [
            'token' => $token,
            'json' => $userData,
        ]);

        $this->assertSame(422, $response->getStatusCode());
        $violations = $response->toArray(false)['violations'];
        $this->assertGreaterThan(0, count($violations));
    }

    public static function validRolesProvider(): array
    {
        return [
            'role_user' => [['ROLE_USER']],
            'role_editor' => [['ROLE_EDITOR']],
            'role_admin' => [['ROLE_ADMIN']],
            'multiple_roles' => [['ROLE_USER', 'ROLE_EDITOR', 'ROLE_GEO_ARCHAEOLOGIST']],
        ];
    }

    #[DataProvider('validRolesProvider')]
    public function testPostUserAcceptsValidRoles(array $validRoles): void
    {
        $client = self::createClient();

        $token = $this->getUserToken($client, 'user_admin');

        $userData = [
            'email' => 'validuser'.uniqid().'@example.com', // Unique email to avoid conflicts
            'plainPassword' => 'StrongPass123!',
            'roles' => $validRoles,
        ];

        $response = $this->apiRequest($client, 'POST', '/api/users', [
            'token' => $token,
            'json' => $userData,
        ]);

        $this->assertSame(201, $response->getStatusCode());

        $responseData = $response->toArray();
        foreach ($validRoles as $role) {
            $this->assertContains($role, $responseData['roles']);
        }
    }

    public function testPostUserValidatesRequiredFields(): void
    {
        $client = self::createClient();

        $token = $this->getUserToken($client, 'user_admin');

        // Test missing email
        $response = $this->apiRequest($client, 'POST', '/api/users', [
            'token' => $token,
            'json' => [
                'plainPassword' => 'StrongPass123',
                'roles' => ['ROLE_USER'],
            ],
        ]);

        $this->assertSame(422, $response->getStatusCode());

        // Test missing password
        $response = $this->apiRequest($client, 'POST', '/api/users', [
            'token' => $token,
            'json' => [
                'email' => 'test@example.com',
                'roles' => ['ROLE_USER'],
            ],
        ]);

        $this->assertSame(422, $response->getStatusCode());

        // Test missing roles
        $response = $this->apiRequest($client, 'POST', '/api/users', [
            'token' => $token,
            'json' => [
                'email' => 'test@example.com',
                'plainPassword' => 'StrongPass123',
            ],
        ]);

        $this->assertSame(422, $response->getStatusCode());
    }

    public function testPostUserHashesPassword(): void
    {
        $client = self::createClient();

        $token = $this->getUserToken($client, 'user_admin');

        $plainPassword = 'StrongPass123!';
        $userData = [
            'email' => 'hasheduser@example.com',
            'plainPassword' => $plainPassword,
            'roles' => ['ROLE_USER'],
        ];

        $response = $this->apiRequest($client, 'POST', '/api/users', [
            'token' => $token,
            'json' => $userData,
        ]);

        $this->assertSame(201, $response->getStatusCode());

        // Verify password is not returned in response
        $responseData = $response->toArray();
        $this->assertArrayNotHasKey('password', $responseData);
        $this->assertArrayNotHasKey('plainPassword', $responseData);

        // Verify we can login with the created user
        $loginResponse = $this->apiRequest($client, 'POST', '/api/login', [
            'json' => [
                'email' => 'hasheduser@example.com',
                'password' => $plainPassword,
            ],
        ]);

        $this->assertSame(200, $loginResponse->getStatusCode());
    }

    // Change Password Operation Tests

    public function testChangePasswordIsDeniedForAnonymousUser(): void
    {
        $client = self::createClient();

        $changePasswordData = [
            'oldPassword' => 'OldPassword123!',
            'plainPassword' => 'NewPassword123!',
            'repeatPassword' => 'NewPassword123!',
        ];

        $response = $this->apiRequest($client, 'POST', '/api/users/me/change_password', [
            'json' => $changePasswordData,
        ]);

        $this->assertSame(401, $response->getStatusCode());
    }

    #[DataProvider('nonAdminUserProvider')]
    public function testChangePasswordIsAllowedForAuthenticatedUser(string $username): void
    {
        $client = self::createClient();
        $oldPassword = $this->parameterBag->get("app.alice.parameters.{$username}_pw");

        $token = $this->getUserToken($client, $username);

        $newPassword = 'NewPassword123!';
        $changePasswordData = [
            'oldPassword' => $oldPassword,
            'plainPassword' => $newPassword,
            'repeatPassword' => $newPassword,
        ];

        $response = $this->apiRequest($client, 'POST', '/api/users/me/change_password', [
            'token' => $token,
            'json' => $changePasswordData,
        ]);

        $this->assertSame(204, $response->getStatusCode());

        // Verify old password no longer works
        $loginResponse = $this->apiRequest($client, 'POST', '/api/login', [
            'json' => [
                'email' => "$username@example.com",
                'password' => $oldPassword,
            ],
        ]);

        $this->assertSame(401, $loginResponse->getStatusCode());

        // Verify new password works
        $loginResponse = $this->apiRequest($client, 'POST', '/api/login', [
            'json' => [
                'email' => "$username@example.com",
                'password' => $newPassword,
            ],
        ]);

        $this->assertSame(200, $loginResponse->getStatusCode());
    }

    public function testChangePasswordValidatesRequiredFields(): void
    {
        $client = self::createClient();
        $username = 'user_base';
        $oldPassword = $this->parameterBag->get("app.alice.parameters.{$username}_pw");

        $token = $this->getUserToken($client, $username);

        // Test missing oldPassword
        $response = $this->apiRequest($client, 'POST', '/api/users/me/change_password', [
            'token' => $token,
            'json' => [
                'plainPassword' => 'NewPassword123!',
                'repeatPassword' => 'NewPassword123!',
            ],
        ]);

        $this->assertSame(422, $response->getStatusCode());

        // Test missing plainPassword
        $response = $this->apiRequest($client, 'POST', '/api/users/me/change_password', [
            'token' => $token,
            'json' => [
                'oldPassword' => $oldPassword,
                'repeatPassword' => 'NewPassword123!',
            ],
        ]);

        $this->assertSame(422, $response->getStatusCode());
        $violations = $response->toArray(false)['violations'];
        $this->assertGreaterThan(0, count($violations));

        // Test missing repeatPassword
        $response = $this->apiRequest($client, 'POST', '/api/users/me/change_password', [
            'token' => $token,
            'json' => [
                'oldPassword' => $oldPassword,
                'plainPassword' => 'NewPassword123!',
            ],
        ]);

        $this->assertSame(422, $response->getStatusCode());
        $violations = $response->toArray(false)['violations'];
        $this->assertGreaterThan(0, count($violations));
    }

    public function testChangePasswordValidatesOldPassword(): void
    {
        $client = self::createClient();
        $username = 'user_base';
        $oldPassword = $this->parameterBag->get("app.alice.parameters.{$username}_pw");

        $token = $this->getUserToken($client, $username);

        $changePasswordData = [
            'oldPassword' => 'WrongOldPassword123!',
            'plainPassword' => 'NewPassword123!',
            'repeatPassword' => 'NewPassword123!',
        ];

        $response = $this->apiRequest($client, 'POST', '/api/users/me/change_password', [
            'token' => $token,
            'json' => $changePasswordData,
        ]);

        $this->assertSame(401, $response->getStatusCode());
    }

    #[DataProvider('invalidPasswordProvider')]
    public function testChangePasswordValidatesNewPasswordStrength(string $invalidPassword): void
    {
        $client = self::createClient();
        $username = 'user_base';
        $oldPassword = $this->parameterBag->get("app.alice.parameters.{$username}_pw");

        $token = $this->getUserToken($client, $username);

        $changePasswordData = [
            'oldPassword' => $oldPassword,
            'plainPassword' => $invalidPassword,
            'repeatPassword' => $invalidPassword,
        ];

        $response = $this->apiRequest($client, 'POST', '/api/users/me/change_password', [
            'token' => $token,
            'json' => $changePasswordData,
        ]);

        $this->assertSame(422, $response->getStatusCode());
        $violations = $response->toArray(false)['violations'];
        $this->assertGreaterThan(0, count($violations));
    }

    public function testChangePasswordValidatesPasswordConfirmation(): void
    {
        $client = self::createClient();
        $username = 'user_base';
        $oldPassword = $this->parameterBag->get("app.alice.parameters.{$username}_pw");

        $token = $this->getUserToken($client, $username);

        $changePasswordData = [
            'oldPassword' => $oldPassword,
            'plainPassword' => 'NewPassword123!',
            'repeatPassword' => 'DifferentPassword123!',
        ];

        $response = $this->apiRequest($client, 'POST', '/api/users/me/change_password', [
            'token' => $token,
            'json' => $changePasswordData,
        ]);

        $this->assertSame(422, $response->getStatusCode());
        $violations = $response->toArray(false)['violations'];
        $this->assertGreaterThan(0, count($violations));

        // Check that the violation is about password mismatch
        $passwordMismatchFound = false;
        foreach ($violations as $violation) {
            if (str_contains($violation['propertyPath'], 'repeatPassword')) {
                $passwordMismatchFound = true;
                break;
            }
        }
        $this->assertTrue($passwordMismatchFound, 'Password mismatch validation should be triggered');
    }

    public function testChangePasswordWithEmptyFields(): void
    {
        $client = self::createClient();
        $username = 'user_base';
        $oldPassword = $this->parameterBag->get("app.alice.parameters.{$username}_pw");

        $token = $this->getUserToken($client, $username);

        $changePasswordData = [
            'oldPassword' => '',
            'plainPassword' => '',
            'repeatPassword' => '',
        ];

        $response = $this->apiRequest($client, 'POST', '/api/users/me/change_password', [
            'token' => $token,
            'json' => $changePasswordData,
        ]);

        $this->assertSame(422, $response->getStatusCode());
        $violations = $response->toArray(false)['violations'];
        $this->assertGreaterThanOrEqual(3, count($violations)); // At least one violation for each field
    }

    public function testChangePasswordReturnsNoContent(): void
    {
        $client = self::createClient();
        $username = 'user_editor';
        $oldPassword = $this->parameterBag->get("app.alice.parameters.{$username}_pw");

        $token = $this->getUserToken($client, $username);

        $newPassword = 'NewPassword123!';
        $changePasswordData = [
            'oldPassword' => $oldPassword,
            'plainPassword' => $newPassword,
            'repeatPassword' => $newPassword,
        ];

        $response = $this->apiRequest($client, 'POST', '/api/users/me/change_password', [
            'token' => $token,
            'json' => $changePasswordData,
        ]);

        $this->assertSame(204, $response->getStatusCode());

        // Since output is set to false, response should be empty or minimal
        $content = $response->getContent();
        $this->assertTrue(empty($content) || '{}' === $content || 'null' === $content);
    }

    // Admin Change Password Operation Tests

    public function testAdminChangePasswordIsDeniedForAnonymousUser(): void
    {
        $client = self::createClient();
        $users = $this->getUsers();
        $targetUserId = $users[0]['id'];

        $changePasswordData = [
            'plainPassword' => 'NewPassword123!',
            'repeatPassword' => 'NewPassword123!',
        ];

        $response = $this->apiRequest($client, 'PATCH', "/api/users/{$targetUserId}/change_password", [
            'json' => $changePasswordData,
        ]);

        $this->assertSame(401, $response->getStatusCode());
    }

    #[DataProvider('nonAdminUserProvider')]
    public function testAdminChangePasswordIsDeniedForNonAdminUser(string $username): void
    {
        $client = self::createClient();
        $users = $this->getUsers();
        $targetUserId = $users[0]['id'];

        $token = $this->getUserToken($client, $username);

        $changePasswordData = [
            'plainPassword' => 'NewPassword123!',
            'repeatPassword' => 'NewPassword123!',
        ];

        $response = $this->apiRequest($client, 'PATCH', "/api/users/{$targetUserId}/change_password", [
            'token' => $token,
            'json' => $changePasswordData,
        ]);

        $this->assertSame(403, $response->getStatusCode());
    }

    public function testAdminChangePasswordIsAllowedForAdminUser(): void
    {
        $client = self::createClient();
        $users = $this->getUsers();
        $targetUser = $users[0];
        $targetUserId = $targetUser['id'];
        $targetUserEmail = $targetUser['email'];

        $token = $this->getUserToken($client, 'user_admin');

        $newPassword = 'AdminSetPassword123!';
        $changePasswordData = [
            'plainPassword' => $newPassword,
        ];

        $response = $this->apiRequest($client, 'PATCH', "/api/users/{$targetUserId}/change_password", [
            'token' => $token,
            'json' => $changePasswordData,
        ]);

        $this->assertSame(204, $response->getStatusCode());

        // Verify new password works for the target user
        $loginResponse = $this->apiRequest($client, 'POST', '/api/login', [
            'json' => [
                'email' => $targetUserEmail,
                'password' => $newPassword,
            ],
        ]);

        $this->assertSame(200, $loginResponse->getStatusCode());
    }

    public function testAdminChangePasswordValidatesRequiredFields(): void
    {
        $client = self::createClient();
        $users = $this->getUsers();
        $targetUserId = $users[0]['id'];

        $token = $this->getUserToken($client, 'user_admin');

        // Test missing plainPassword
        $response = $this->apiRequest($client, 'PATCH', "/api/users/{$targetUserId}/change_password", [
            'token' => $token,
            'json' => [
            ],
        ]);

        $this->assertSame(422, $response->getStatusCode());
        $violations = $response->toArray(false)['violations'];
        $this->assertGreaterThan(0, count($violations));
    }

    #[DataProvider('invalidPasswordProvider')]
    public function testAdminChangePasswordValidatesNewPasswordStrength(string $invalidPassword): void
    {
        $client = self::createClient();
        $users = $this->getUsers();
        $targetUserId = $users[0]['id'];

        $token = $this->getUserToken($client, 'user_admin');

        $changePasswordData = [
            'plainPassword' => $invalidPassword,
            'repeatPassword' => $invalidPassword,
        ];

        $response = $this->apiRequest($client, 'PATCH', "/api/users/{$targetUserId}/change_password", [
            'token' => $token,
            'json' => $changePasswordData,
        ]);

        $this->assertSame(422, $response->getStatusCode());
        $violations = $response->toArray(false)['violations'];
        $this->assertGreaterThan(0, count($violations));
    }
}
