<?php

namespace App\Tests\Functional\Api\Resource\Geoserver;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Tests\Functional\Api\ApiTestProviderTrait;
use App\Tests\Functional\ApiTestRequestTrait;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class ApiResourcePotteryGeoserverTest extends ApiTestCase
{
    use ApiTestRequestTrait;
    use ApiTestProviderTrait;

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

    public function testGetCollectionJsonUnfiltered(): void
    {
        $client = self::createClient();

        $token = $this->getUserToken($client, 'user_editor');

        $collectionResponse = $this->apiRequest($client, 'GET', '/api/data/features/potteries', [
            'token' => $token,
            'headers' => [
                'Accept' => 'application/json',
            ],
        ]);
        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseHeaderSame('content-type', 'application/json');
        $responseJson = json_decode($collectionResponse->getContent(), true);

        // Unfiltered should return true according to plan if all match
        $this->assertTrue($responseJson);
    }

    public function testGetCollectionJsonFiltered(): void
    {
        $client = self::createClient();

        $token = $this->getUserToken($client, 'user_editor');

        // Assuming there is some data in the test database
        $collectionResponse = $this->apiRequest($client, 'GET', '/api/data/features/potteries?number=1', [
            'token' => $token,
            'headers' => [
                'Accept' => 'application/json',
            ],
        ]);
        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseHeaderSame('content-type', 'application/json');
        $responseJson = json_decode($collectionResponse->getContent(), true);

        // Should return a map {parentId: count} or true if all match
        // If filtered, it should likely be an array (map)
        $this->assertTrue(is_array($responseJson) || is_bool($responseJson));
    }

    public function testGetCollectionGeoJson(): void
    {
        $client = self::createClient();

        $token = $this->getUserToken($client, 'user_editor');

        $collectionResponse = $this->apiRequest($client, 'GET', '/api/data/features/potteries', [
            'token' => $token,
            'headers' => [
                'Accept' => 'application/geo+json',
            ],
        ]);

        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseHeaderSame('content-type', 'application/geo+json; charset=utf-8');

        $responseJson = json_decode($collectionResponse->getContent(), true);
        $this->assertSame('FeatureCollection', $responseJson['type']);

        if (!empty($responseJson['features'])) {
            $firstFeature = $responseJson['features'][0];
            $this->assertArrayHasKey('number_matched', $firstFeature['properties']);
        }
    }

    public function testGetNumberMatched(): void
    {
        $client = self::createClient();

        $token = $this->getUserToken($client, 'user_editor');

        $collectionResponse = $this->apiRequest($client, 'GET', '/api/data/features/number_matched/potteries', [
            'token' => $token,
        ]);
        $this->assertResponseStatusCodeSame(200);
        $responseArray = $collectionResponse->toArray();
        $this->assertArrayHasKey('numberMatched', $responseArray);
    }

    public function testGetExtentMatched(): void
    {
        $client = self::createClient();

        $token = $this->getUserToken($client, 'user_editor');

        $collectionResponse = $this->apiRequest($client, 'GET', '/api/data/features/extent_matched/potteries', [
            'token' => $token,
        ]);
        $this->assertResponseStatusCodeSame(200);
        $responseArray = $collectionResponse->toArray();
        $this->assertArrayHasKey('extent', $responseArray);
    }

    public function testGetExport(): void
    {
        $client = self::createClient();

        $token = $this->getUserToken($client, 'user_editor');

        $collectionResponse = $this->apiRequest($client, 'GET', '/api/data/features/export/potteries', [
            'token' => $token,
        ]);
        $this->assertResponseStatusCodeSame(200);
    }
}
