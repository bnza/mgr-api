<?php

namespace App\Tests\Functional\Api\Resource\Geoserver;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Tests\Functional\Api\ApiTestProviderTrait;
use App\Tests\Functional\ApiTestRequestTrait;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class ApiResourcePaleoclimateSampleGeoserverTest extends ApiTestCase
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

        $collectionResponse = $this->apiRequest($client, 'GET', '/api/features/paleoclimate_samples', [
            'token' => $token,
            'headers' => [
                'Accept' => 'application/json',
            ],
        ]);
        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseHeaderSame('content-type', 'application/json');
        $responseJson = json_decode($collectionResponse->getContent(), true);

        // Unfiltered should return true according to plan if all match
        $this->assertIsArray($responseJson);
        $this->assertNotEmpty($responseJson);
        foreach ($responseJson as $count) {
            $this->assertGreaterThan(0, $count);
        }
    }

    public function testGetCollectionJsonFiltered(): void
    {
        $client = self::createClient();

        $token = $this->getUserToken($client, 'user_editor');

        $collectionResponse = $this->apiRequest($client, 'GET', '/api/features/paleoclimate_samples?id[]=1', [
            'token' => $token,
            'headers' => [
                'Accept' => 'application/json',
            ],
        ]);
        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseHeaderSame('content-type', 'application/json');
        $responseJson = json_decode($collectionResponse->getContent(), true);

        $this->assertTrue(is_array($responseJson) || is_bool($responseJson));
    }

    public function testGetCollectionGeoJson(): void
    {
        $client = self::createClient();

        $token = $this->getUserToken($client, 'user_editor');

        $collectionResponse = $this->apiRequest($client, 'GET', '/api/features/paleoclimate_samples', [
            'token' => $token,
            'headers' => [
                'Accept' => 'application/geo+json',
            ],
        ]);

        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseHeaderSame('content-type', 'application/geo+json; charset=utf-8');
    }

    public function testGetNumberMatched(): void
    {
        $client = self::createClient();

        $token = $this->getUserToken($client, 'user_editor');

        $collectionResponse = $this->apiRequest($client, 'GET', '/api/features/number_matched/paleoclimate_samples', [
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

        $collectionResponse = $this->apiRequest($client, 'GET', '/api/features/extent_matched/paleoclimate_samples', [
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

        $collectionResponse = $this->apiRequest($client, 'GET', '/api/features/export/paleoclimate_samples', [
            'token' => $token,
        ]);
        $this->assertResponseStatusCodeSame(200);
    }
}
