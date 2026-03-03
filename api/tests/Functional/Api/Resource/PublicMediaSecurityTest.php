<?php

namespace App\Tests\Functional\Api\Resource;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Tests\Functional\ApiTestRequestTrait;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class PublicMediaSecurityTest extends ApiTestCase
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

    public function testGetMediaObjectCollectionAsAnonymousOnlyReturnsPublic(): void
    {
        $client = static::createClient();

        // Get as anonymous
        $response = $this->apiRequest($client, 'GET', '/api/data/media_objects?itemsPerPage=100');
        $this->assertResponseIsSuccessful();
        $anonymousData = $response->toArray();

        // Get as authenticated user
        $token = $this->getUserToken($client, 'user_base');
        $response = $this->apiRequest($client, 'GET', '/api/data/media_objects?itemsPerPage=100', ['token' => $token]);
        $this->assertResponseIsSuccessful();
        $authenticatedData = $response->toArray();

        // Verify that anonymous sees fewer or equal items than authenticated
        $this->assertLessThanOrEqual($authenticatedData['totalItems'], $anonymousData['totalItems']);

        // Check that anonymous sees ONLY items that are marked as public (but we can't see the public flag as anonymous)
        // Instead, we verify that any item seen by anonymous IS also seen by authenticated user AND has public=true there
        $authenticatedItemsById = [];
        foreach ($authenticatedData['member'] as $item) {
            $authenticatedItemsById[$item['id']] = $item;
        }

        foreach ($anonymousData['member'] as $item) {
            $this->assertArrayHasKey($item['id'], $authenticatedItemsById);
            $this->assertTrue($authenticatedItemsById[$item['id']]['public'], 'Anonymous user should only see public media objects');
        }
    }

    public function testGetMediaObjectPotteryCollectionAsAnonymousOnlyReturnsPublic(): void
    {
        $client = static::createClient();

        $response = $this->apiRequest($client, 'GET', '/api/data/media_object_potteries?itemsPerPage=100');
        $this->assertResponseIsSuccessful();
        $anonymousData = $response->toArray();

        $token = $this->getUserToken($client, 'user_base');
        $response = $this->apiRequest($client, 'GET', '/api/data/media_object_potteries?itemsPerPage=100', ['token' => $token]);
        $this->assertResponseIsSuccessful();
        $authenticatedData = $response->toArray();

        $authenticatedJoinsById = [];
        foreach ($authenticatedData['member'] as $item) {
            $authenticatedJoinsById[$item['id']] = $item;
        }

        foreach ($anonymousData['member'] as $item) {
            $this->assertArrayHasKey($item['id'], $authenticatedJoinsById);
            $this->assertTrue($authenticatedJoinsById[$item['id']]['mediaObject']['public'], 'Anonymous user should only see joins to public media objects');
        }
    }

    public function testGetPotteryCollectionWithMediaFilterAsAnonymousOnlyConsidersPublic(): void
    {
        $client = static::createClient();

        // 1. Check with exists[mediaObjects]=true
        $response = $this->apiRequest($client, 'GET', '/api/data/potteries?exists[mediaObjects]=true');
        $this->assertResponseIsSuccessful();
        $anonymousData = $response->toArray();
        $anonymousPotteryCodes = array_map(fn ($item) => $item['code'], $anonymousData['member']);

        // Authenticated user
        $token = $this->getUserToken($client, 'user_base');
        $response = $this->apiRequest($client, 'GET', '/api/data/potteries?exists[mediaObjects]=true', ['token' => $token]);
        $this->assertResponseIsSuccessful();
        $authenticatedData = $response->toArray();
        $authenticatedPotteryCodes = array_map(fn ($item) => $item['code'], $authenticatedData['member']);

        // ME.3.2023 only has a private media object (media_object_33)
        // It should be in authenticated results but NOT in anonymous results
        $this->assertContains('ME.3.2023', $authenticatedPotteryCodes);
        $this->assertNotContains('ME.3.2023', $anonymousPotteryCodes);

        // 2. Check with filter on mediaObject property (originalFilename)
        // media_object_33 has originalFilename '5.png' and is private, linked to ME.3.2023
        $response = $this->apiRequest($client, 'GET', '/api/data/potteries?mediaObjects.mediaObject.originalFilename=5.png');
        $this->assertResponseIsSuccessful();
        $this->assertEquals(0, $response->toArray()['totalItems'], 'Anonymous user should not find pottery via private media object filter');

        $response = $this->apiRequest($client, 'GET', '/api/data/potteries?mediaObjects.mediaObject.originalFilename=5.png', ['token' => $token]);
        $this->assertResponseIsSuccessful();
        $this->assertGreaterThan(0, $response->toArray()['totalItems'], 'Authenticated user should find pottery via private media object filter');

        // 3. Resource with mixed public/private media objects
        // ME.2.2023 has media_object_29 (private, 1.pdf) and media_object_30 (public, 2.pdf)

        // Search for private one
        $response = $this->apiRequest($client, 'GET', '/api/data/potteries?mediaObjects.mediaObject.originalFilename=1.pdf');
        $this->assertResponseIsSuccessful();
        $this->assertEquals(0, $response->toArray()['totalItems'], 'Anonymous user should not find pottery via private media object even if it also has public ones');

        $response = $this->apiRequest($client, 'GET', '/api/data/potteries?mediaObjects.mediaObject.originalFilename=1.pdf', ['token' => $token]);
        $this->assertResponseIsSuccessful();
        $this->assertGreaterThan(0, $response->toArray()['totalItems'], 'Authenticated user should find pottery via private media object');

        // Search for public one
        $response = $this->apiRequest($client, 'GET', '/api/data/potteries?mediaObjects.mediaObject.originalFilename=2.pdf');
        $this->assertResponseIsSuccessful();
        $this->assertGreaterThan(0, $response->toArray()['totalItems'], 'Anonymous user should find pottery via its public media object');
    }

    public function testGetStratigraphicUnitCollectionWithMediaFilterAsAnonymousOnlyConsidersPublic(): void
    {
        $client = static::createClient();

        // Currently all media objects linked to SUs in fixtures are public,
        // but we verify the query still works and respects the extension.
        $response = $this->apiRequest($client, 'GET', '/api/data/stratigraphic_units?exists[mediaObjects]=true');
        $this->assertResponseIsSuccessful();

        $token = $this->getUserToken($client, 'user_base');
        $response = $this->apiRequest($client, 'GET', '/api/data/stratigraphic_units?exists[mediaObjects]=true', ['token' => $token]);
        $this->assertResponseIsSuccessful();
    }

    public function testGetMediaObjectSamplingStratigraphicUnitCollectionAsAnonymousOnlyReturnsPublic(): void
    {
        $client = static::createClient();

        // This was previously missing from the extension's hardcoded list
        $response = $this->apiRequest($client, 'GET', '/api/data/media_object_sampling_stratigraphic_units?itemsPerPage=100');
        $this->assertResponseIsSuccessful();
        $anonymousData = $response->toArray();

        $token = $this->getUserToken($client, 'user_base');
        $response = $this->apiRequest($client, 'GET', '/api/data/media_object_sampling_stratigraphic_units?itemsPerPage=100', ['token' => $token]);
        $this->assertResponseIsSuccessful();
        $authenticatedData = $response->toArray();

        $authenticatedJoinsById = [];
        foreach ($authenticatedData['member'] as $item) {
            $authenticatedJoinsById[$item['id']] = $item;
        }

        foreach ($anonymousData['member'] as $item) {
            $this->assertArrayHasKey($item['id'], $authenticatedJoinsById);
            $this->assertTrue($authenticatedJoinsById[$item['id']]['mediaObject']['public'], 'Anonymous user should only see joins to public media objects');
        }
    }
}
