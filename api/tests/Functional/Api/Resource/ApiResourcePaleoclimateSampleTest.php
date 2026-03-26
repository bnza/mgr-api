<?php

declare(strict_types=1);

namespace App\Tests\Functional\Api\Resource;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Entity\Auth\User;
use App\Tests\Functional\ApiTestRequestTrait;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class ApiResourcePaleoclimateSampleTest extends ApiTestCase
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

        // Testing unaccented filter for description
        $response = $this->apiRequest($client, 'GET', '/api/data/paleoclimate_samples?description=stalagmite');
        $this->assertResponseIsSuccessful();
        $data = $response->toArray();
        $this->assertGreaterThan(0, $data['totalItems']);
        foreach ($data['member'] as $item) {
            $this->assertStringContainsStringIgnoringCase('stalagmite', $item['description']);
        }
    }

    public function testCreateUpdatePaleoclimateSample(): void
    {
        $client = self::createClient();
        $token = $this->getUserToken($client, 'user_admin');

        // Need a site first
        $siteResponse = $this->apiRequest($client, 'GET', '/api/data/paleoclimate_sampling_sites');
        $this->assertResponseIsSuccessful();
        $siteIri = $siteResponse->toArray()['member'][0]['@id'];

        $number = 999;
        $description = 'Stalagmite test description with accents: balaghī';
        $chronologyLower = 1000;
        $chronologyUpper = 1500;
        $length = 250;

        // 1. Create
        $response = $this->apiRequest($client, 'POST', '/api/data/paleoclimate_samples', [
            'token' => $token,
            'json' => [
                'site' => $siteIri,
                'number' => $number,
                'description' => $description,
                'chronologyLower' => $chronologyLower,
                'chronologyUpper' => $chronologyUpper,
                'length' => $length,
                'temperatureRecord' => true,
                'precipitationRecord' => false,
                'stableIsotopes' => true,
                'traceElements' => false,
                'petrographicDescriptions' => true,
                'fluidInclusions' => false,
            ],
        ]);

        $this->assertSame(201, $response->getStatusCode());
        $data = $response->toArray();
        $this->assertEquals($number, $data['number']);
        $this->assertEquals($description, $data['description']);
        $this->assertEquals($chronologyLower, $data['chronologyLower']);
        $this->assertEquals($chronologyUpper, $data['chronologyUpper']);
        $this->assertEquals($length, $data['length']);
        $this->assertTrue($data['temperatureRecord']);
        $this->assertFalse($data['precipitationRecord']);
        $iri = $data['@id'];

        // 2. Update (Patch)
        $newDescription = $description.' Updated';
        $response = $this->apiRequest($client, 'PATCH', $iri, [
            'token' => $token,
            'json' => [
                'description' => $newDescription,
                'length' => 300,
            ],
        ]);

        $this->assertResponseIsSuccessful();
        $data = $response->toArray();
        $this->assertEquals($newDescription, $data['description']);
        $this->assertEquals(300, $data['length']);

        // 3. Verify via GET
        $response = $this->apiRequest($client, 'GET', $iri, [
            'token' => $token,
        ]);
        $this->assertResponseIsSuccessful();
        $data = $response->toArray();
        $this->assertEquals($newDescription, $data['description']);
        $this->assertEquals($number, $data['number']);
    }

    public function testSearchFilterGetCollection(): void
    {
        $client = self::createClient();

        // Get an existing sample
        $response = $this->apiRequest($client, 'GET', '/api/data/paleoclimate_samples');
        $this->assertResponseIsSuccessful();
        $data = $response->toArray();
        $this->assertNotEmpty($data['member']);
        $existingSample = $data['member'][0];
        $existingSiteIri = $existingSample['site']['@id'];
        $existingNumber = $existingSample['number'];

        // Filter by site and number
        $response = $this->apiRequest($client, 'GET', '/api/data/paleoclimate_samples?site='.$existingSiteIri.'&number='.$existingNumber);
        $this->assertResponseIsSuccessful();
        $data = $response->toArray();
        $this->assertGreaterThan(0, $data['totalItems']);
        foreach ($data['member'] as $item) {
            $this->assertEquals($existingSiteIri, $item['site']['@id']);
            $this->assertEquals($existingNumber, $item['number']);
        }
    }

    public function testAdminCanDeletePaleoclimateSample(): void
    {
        $client = self::createClient();
        $adminToken = $this->getUserToken($client, 'user_admin');

        // Create a sample to delete
        $siteResponse = $this->apiRequest($client, 'GET', '/api/data/paleoclimate_sampling_sites');
        $siteIri = $siteResponse->toArray()['member'][0]['@id'];

        $response = $this->apiRequest($client, 'POST', '/api/data/paleoclimate_samples', [
            'token' => $adminToken,
            'json' => [
                'site' => $siteIri,
                'number' => 8888,
                'description' => 'To be deleted',
            ],
        ]);
        $this->assertSame(201, $response->getStatusCode());
        $iri = $response->toArray()['@id'];

        // Delete
        $deleteResponse = $this->apiRequest($client, 'DELETE', $iri, ['token' => $adminToken]);
        $this->assertSame(204, $deleteResponse->getStatusCode());

        // Verify it's gone
        $response = $this->apiRequest($client, 'GET', $iri, ['token' => $adminToken]);
        $this->assertSame(404, $response->getStatusCode());
    }

    public function testPaleoclimateSampleNumberValidationNotBlank(): void
    {
        $client = self::createClient();
        $token = $this->getUserToken($client, 'user_admin');

        $siteResponse = $this->apiRequest($client, 'GET', '/api/data/paleoclimate_sampling_sites');
        $siteIri = $siteResponse->toArray()['member'][0]['@id'];

        $response = $this->apiRequest($client, 'POST', '/api/data/paleoclimate_samples', [
            'token' => $token,
            'json' => [
                'site' => $siteIri,
                // 'number' is missing
            ],
        ]);

        $this->assertResponseStatusCodeSame(422);
        $this->assertJsonContains(['violations' => [['propertyPath' => 'number', 'message' => 'This value should not be blank.']]]);
    }

    public function testPaleoclimateSampleSiteValidationNotBlank(): void
    {
        $client = self::createClient();
        $token = $this->getUserToken($client, 'user_admin');

        $response = $this->apiRequest($client, 'POST', '/api/data/paleoclimate_samples', [
            'token' => $token,
            'json' => [
                'number' => 777,
                // 'site' is missing
            ],
        ]);

        $this->assertResponseStatusCodeSame(422);
        $this->assertJsonContains(['violations' => [['propertyPath' => 'site', 'message' => 'This value should not be blank.']]]);
    }

    public function testPaleoclimateSampleUniqueConstraint(): void
    {
        $client = self::createClient();
        $token = $this->getUserToken($client, 'user_admin');

        // Get an existing sample's site and number
        $response = $this->apiRequest($client, 'GET', '/api/data/paleoclimate_samples');
        $existingSample = $response->toArray()['member'][0];
        $existingSiteIri = $existingSample['site']['@id'];
        $existingNumber = $existingSample['number'];

        $response = $this->apiRequest($client, 'POST', '/api/data/paleoclimate_samples', [
            'token' => $token,
            'json' => [
                'site' => $existingSiteIri,
                'number' => $existingNumber,
                'description' => 'Duplicate',
            ],
        ]);

        $this->assertResponseStatusCodeSame(422);
        // Assuming standard UniqueEntity message for the combined constraint
        $this->assertJsonContains(['violations' => [['message' => 'Duplicate [site, number] combination.']]]);
    }

    public function testEditorPaleoclimatologistCanManagePaleoclimateSamples(): void
    {
        $client = self::createClient();

        // Create user with both roles
        $email = 'editor_paleo_sample@example.com';
        $password = 'password123';
        $this->createUser($email, $password, ['ROLE_EDITOR', 'ROLE_PALEOCLIMATOLOGIST']);

        $token = $this->getUserToken($client, $email, $password);

        // 1. GET /api/data/paleoclimate_samples and check _acl: {canCreate: true}
        $response = $this->apiRequest($client, 'GET', '/api/data/paleoclimate_samples', ['token' => $token]);
        $this->assertResponseIsSuccessful();
        $data = $response->toArray();
        $this->assertArrayHasKey('_acl', $data);
        $this->assertTrue($data['_acl']['canCreate']);

        // 1b. Check canCreate in sub-collection /api/data/paleoclimate_sampling_sites/{id}/samples
        $siteResponse = $this->apiRequest($client, 'GET', '/api/data/paleoclimate_sampling_sites');
        $this->assertResponseIsSuccessful();
        $siteId = $siteResponse->toArray()['member'][0]['id'];
        $subCollectionResponse = $this->apiRequest($client, 'GET', "/api/data/paleoclimate_sampling_sites/$siteId/samples", ['token' => $token]);
        $this->assertResponseIsSuccessful();
        $subCollectionData = $subCollectionResponse->toArray();
        $this->assertArrayHasKey('_acl', $subCollectionData);
        $this->assertTrue($subCollectionData['_acl']['canCreate']);

        // 2. Can create/update a sample
        $siteResponse = $this->apiRequest($client, 'GET', '/api/data/paleoclimate_sampling_sites');
        $siteIri = $siteResponse->toArray()['member'][0]['@id'];
        $number = 6666;

        // Create
        $response = $this->apiRequest($client, 'POST', '/api/data/paleoclimate_samples', [
            'token' => $token,
            'json' => [
                'site' => $siteIri,
                'number' => $number,
                'description' => 'EP Sample',
            ],
        ]);
        $this->assertSame(201, $response->getStatusCode());
        $data = $response->toArray();
        $iri = $data['@id'];

        // Update
        $response = $this->apiRequest($client, 'PATCH', $iri, [
            'token' => $token,
            'json' => [
                'description' => 'EP Sample Updated',
            ],
        ]);
        $this->assertResponseIsSuccessful();
        $this->assertEquals('EP Sample Updated', $response->toArray()['description']);

        // 3. Can delete a sample
        $deleteResponse = $this->apiRequest($client, 'DELETE', $iri, ['token' => $token]);
        $this->assertSame(204, $deleteResponse->getStatusCode());

        // Verify deletion
        $response = $this->apiRequest($client, 'GET', $iri, ['token' => $token]);
        $this->assertSame(404, $response->getStatusCode());
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
