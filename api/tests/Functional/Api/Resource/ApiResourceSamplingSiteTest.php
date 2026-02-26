<?php

declare(strict_types=1);

namespace App\Tests\Functional\Api\Resource;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Entity\Auth\User;
use App\Tests\Functional\ApiTestRequestTrait;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class ApiResourceSamplingSiteTest extends ApiTestCase
{
    use ApiTestRequestTrait;

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

        // Testing unaccented filter for description
        $response = $this->apiRequest($client, 'GET', '/api/data/sampling_sites?description=sampling site 1');
        $this->assertResponseIsSuccessful();
        $data = $response->toArray();
        $this->assertGreaterThan(0, $data['totalItems']);
        foreach ($data['member'] as $item) {
            $this->assertStringContainsStringIgnoringCase('sampling site 1', $item['description']);
        }
    }

    public function testFilterUnaccentedNameGetCollection(): void
    {
        $client = self::createClient();

        // Testing unaccented filter for name
        $response = $this->apiRequest($client, 'GET', '/api/data/sampling_sites?name=Sediment cores');
        $this->assertResponseIsSuccessful();
        $data = $response->toArray();
        $this->assertGreaterThan(0, $data['totalItems']);
        foreach ($data['member'] as $item) {
            $this->assertStringContainsStringIgnoringCase('Sediment cores', $item['name']);
        }
    }

    public function testCreateUpdateSamplingSite(): void
    {
        $client = self::createClient();
        $token = $this->getUserToken($client, 'user_admin');

        $code = 'SSC'.uniqid();
        $name = 'Test Sampling Site '.uniqid();
        $description = 'A test description with accents: balaghī';

        // 1. Create
        $response = $this->apiRequest($client, 'POST', '/api/data/sampling_sites', [
            'token' => $token,
            'json' => [
                'code' => $code,
                'name' => $name,
                'description' => $description,
                'n' => 35.123,
                'e' => 52.456,
            ],
        ]);

        $this->assertResponseStatusCodeSame(201);
        $data = $response->toArray();
        $this->assertEquals(strtoupper($code), $data['code']);
        $this->assertEquals($name, $data['name']);
        $this->assertEquals($description, $data['description']);
        $this->assertEquals(35.123, $data['n']);
        $this->assertEquals(52.456, $data['e']);
        $iri = $data['@id'];

        // 2. Update (Patch)
        $newName = $name.' Updated';
        $response = $this->apiRequest($client, 'PATCH', $iri, [
            'token' => $token,
            'json' => [
                'name' => $newName,
            ],
        ]);

        $this->assertResponseIsSuccessful();
        $data = $response->toArray();
        $this->assertEquals($newName, $data['name']);

        // 3. Verify via GET
        $response = $this->apiRequest($client, 'GET', $iri, [
            'token' => $token,
        ]);
        $this->assertResponseIsSuccessful();
        $data = $response->toArray();
        $this->assertEquals($newName, $data['name']);
        $this->assertEquals(strtoupper($code), $data['code']);
    }

    public function testSearchFilterGetCollection(): void
    {
        $client = self::createClient();

        // Find an existing sampling site code from fixtures
        $response = $this->apiRequest($client, 'GET', '/api/data/sampling_sites');
        $this->assertResponseIsSuccessful();
        $data = $response->toArray();
        $this->assertNotEmpty($data['member']);
        $existingCode = $data['member'][0]['code'];

        $response = $this->apiRequest($client, 'GET', '/api/data/sampling_sites?code='.$existingCode);
        $this->assertResponseIsSuccessful();
        $data = $response->toArray();
        $this->assertGreaterThan(0, $data['totalItems']);
        foreach ($data['member'] as $item) {
            $this->assertEquals($existingCode, $item['code']);
        }
    }

    public function testAdminCanDeleteSamplingSite(): void
    {
        $client = self::createClient();
        $adminToken = $this->getUserToken($client, 'user_admin');

        // Create a site to delete
        $response = $this->createTestSamplingSite($client, $adminToken);
        $this->assertResponseStatusCodeSame(201);
        $iri = $response->toArray()['@id'];

        // Delete
        $this->apiRequest($client, 'DELETE', $iri, ['token' => $adminToken]);
        $this->assertResponseStatusCodeSame(204);

        // Verify it's gone
        $this->apiRequest($client, 'GET', $iri, ['token' => $adminToken]);
        $this->assertResponseStatusCodeSame(404);
    }

    public function testSamplingSiteCodeValidationNotBlank(): void
    {
        $client = self::createClient();
        $token = $this->getUserToken($client, 'user_admin');

        $response = $this->apiRequest($client, 'POST', '/api/data/sampling_sites', [
            'token' => $token,
            'json' => [
                'name' => 'Name',
                'n' => 10,
                'e' => 20,
                // 'code' is missing
            ],
        ]);

        $this->assertResponseStatusCodeSame(422);
        // The NotBlank constraint might return "This value should not be blank."
        $this->assertJsonContains(['violations' => [['propertyPath' => 'code', 'message' => 'This value should not be blank.']]]);
    }

    public function testSamplingSiteNameValidationNotBlank(): void
    {
        $client = self::createClient();
        $token = $this->getUserToken($client, 'user_admin');

        $response = $this->apiRequest($client, 'POST', '/api/data/sampling_sites', [
            'token' => $token,
            'json' => [
                'code' => 'SSC99',
                'n' => 10,
                'e' => 20,
                // 'name' is missing
            ],
        ]);

        $this->assertResponseStatusCodeSame(422);
        $this->assertJsonContains(['violations' => [['propertyPath' => 'name', 'message' => 'This value should not be blank.']]]);
    }

    public function testSamplingSiteUniqueCodeConstraint(): void
    {
        $client = self::createClient();
        $token = $this->getUserToken($client, 'user_admin');

        // Get an existing code
        $response = $this->apiRequest($client, 'GET', '/api/data/sampling_sites');
        $this->assertResponseIsSuccessful();
        $existingCode = $response->toArray()['member'][0]['code'];

        $response = $this->apiRequest($client, 'POST', '/api/data/sampling_sites', [
            'token' => $token,
            'json' => [
                'code' => $existingCode,
                'name' => 'New Name',
                'n' => 10,
                'e' => 20,
            ],
        ]);

        $this->assertResponseStatusCodeSame(422);
        $this->assertJsonContains(['violations' => [['propertyPath' => 'code', 'message' => 'Duplicate sampling site code.']]]);
    }

    public function testDeleteSamplingSiteIsBlockedWhenReferencedByOtherEntities(): void
    {
        $client = self::createClient();
        $token = $this->getUserToken($client, 'user_admin');

        // 1. Find a sampling site referenced by a sediment core
        $response = $this->apiRequest($client, 'GET', '/api/data/sediment_cores', ['token' => $token]);
        $this->assertResponseIsSuccessful();
        $data = $response->toArray();
        $this->assertNotEmpty($data['member']);
        $sedimentCore = $data['member'][0];
        $samplingSiteIri = $sedimentCore['site']['@id'];

        // 2. Try to delete it
        $this->apiRequest($client, 'DELETE', $samplingSiteIri, ['token' => $token]);

        // 3. Expect 422 Unprocessable Entity due to NotReferenced constraint
        $this->assertResponseStatusCodeSame(422);
        $this->assertJsonContains([
            'violations' => [
                [
                    'propertyPath' => '',
                    'message' => 'Cannot delete the sampling site because it is referenced by: SedimentCore.',
                ],
            ],
        ]);
    }

    public function testEditorGeoArchaeologistCanManageSamplingSites(): void
    {
        $client = self::createClient();

        // Create user with both roles
        $email = 'editor_geo@example.com';
        $password = 'password123';
        $this->createUser($email, $password, ['ROLE_EDITOR', 'ROLE_GEO_ARCHAEOLOGIST']);

        $token = $this->getUserToken($client, $email, $password);

        // 1. GET /api/data/sampling_sites and check _acl: {canCreate: true}
        $response = $this->apiRequest($client, 'GET', '/api/data/sampling_sites', ['token' => $token]);
        $this->assertResponseIsSuccessful();
        $data = $response->toArray();
        $this->assertArrayHasKey('_acl', $data);
        $this->assertTrue($data['_acl']['canCreate']);

        // 2. Can create/update a sampling site
        $code = 'EG'.uniqid();
        $name = 'EG Site '.uniqid();

        // Create
        $response = $this->apiRequest($client, 'POST', '/api/data/sampling_sites', [
            'token' => $token,
            'json' => [
                'code' => $code,
                'name' => $name,
                'n' => 10,
                'e' => 20,
            ],
        ]);
        $this->assertResponseStatusCodeSame(201);
        $data = $response->toArray();
        $iri = $data['@id'];
        $this->assertEquals(strtoupper($code), $data['code']);

        // Update
        $response = $this->apiRequest($client, 'PATCH', $iri, [
            'token' => $token,
            'json' => [
                'name' => $name.' Updated',
            ],
        ]);
        $this->assertResponseIsSuccessful();
        $this->assertEquals($name.' Updated', $response->toArray()['name']);

        // 3. Can delete a sampling site
        $this->apiRequest($client, 'DELETE', $iri, ['token' => $token]);
        $this->assertResponseStatusCodeSame(204);

        // Verify deletion
        $this->apiRequest($client, 'GET', $iri, ['token' => $token]);
        $this->assertResponseStatusCodeSame(404);
    }

    private function createUser(string $email, string $password, array $roles): void
    {
        $container = self::getContainer();
        $em = $container->get('doctrine')->getManager();
        $hasher = $container->get('security.user_password_hasher');

        $user = new User();
        $user->setEmail($email);
        $user->setRoles($roles);
        $user->setEnabled(true);
        $user->setPassword($hasher->hashPassword($user, $password));

        $em->persist($user);
        $em->flush();
    }
}
