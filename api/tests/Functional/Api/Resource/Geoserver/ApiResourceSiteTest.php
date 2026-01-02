<?php

namespace App\Tests\Functional\Api\Resource\Geoserver;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Tests\Functional\Api\ApiTestProviderTrait;
use App\Tests\Functional\ApiTestRequestTrait;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class ApiResourceSiteTest extends ApiTestCase
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

        $collectionResponse = $this->apiRequest($client, 'GET', '/api/features/sites?bbox=477474.3708727881,3803391.521162848,3814213.6816450283,5703600.6934222365,EPSG:3857', [
            'headers' => [
                'Accept' => 'application/json',
            ],
        ]);
        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseHeaderSame('content-type', 'application/json');
        $responseJson = json_decode($collectionResponse->getContent());
        $this->assertSame(true, $responseJson);
    }

    public function testGetCollectionJsonFiltered(): void
    {
        $client = self::createClient();

        $collectionResponse = $this->apiRequest($client, 'GET', '/api/features/sites?name=sediment&bbox=477474.3708727881,3803391.521162848,3814213.6816450283,5703600.6934222365,EPSG:3857', [
            'headers' => [
                'Accept' => 'application/json',
            ],
        ]);
        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseHeaderSame('content-type', 'application/json');
        $responseJson = json_decode($collectionResponse->getContent());
        $this->assertIsArray($responseJson);
        $this->assertNotEmpty($responseJson);
        $this->assertContainsOnlyInt($responseJson);
    }

    public function testGetCollectionUnfiltered(): void
    {
        $client = self::createClient();

        $collectionResponse = $this->apiRequest($client, 'GET', '/api/features/sites?bbox=477474.3708727881,3803391.521162848,3814213.6816450283,5703600.6934222365,EPSG:3857', [
            'headers' => [
                'Accept' => 'application/geo+json',
            ],
        ]);
        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseHeaderSame('content-type', 'application/geo+json; charset=utf-8');
    }

    public function testGetCollectionWithHeaders(): void
    {
        $client = self::createClient();

        $collectionResponse = $this->apiRequest($client, 'GET', '/api/features/sites?name=sediment&bbox=477474.3708727881,3803391.521162848,3814213.6816450283,5703600.6934222365,EPSG:3857', [
            'headers' => [
                'Accept' => 'application/geo+json',
            ],
        ]);
        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseHeaderSame('content-type', 'application/geo+json; charset=utf-8');
    }

    public function testGetCollectionNumberMatchedUnfiltered(): void
    {
        $client = self::createClient();

        $collectionResponse = $this->apiRequest($client, 'GET', '/api/features/number_matched/sites?bbox=477474.3708727881,3803391.521162848,3814213.6816450283,5703600.6934222365,EPSG:3857');
        $this->assertResponseStatusCodeSame(200);
        $responseArray = $collectionResponse->toArray();
        $this->assertArrayHasKey('numberMatched', $responseArray);
        $this->assertGreaterThan(
            0, $responseArray['numberMatched']
        );
    }

    public function testGetCollectionNumberMatched(): void
    {
        $client = self::createClient();

        $collectionResponse = $this->apiRequest($client, 'GET', '/api/features/number_matched/sites?name=sediment&bbox=477474.3708727881,3803391.521162848,3814213.6816450283,5703600.6934222365,EPSG:3857');
        $this->assertResponseStatusCodeSame(200);
        $responseArray = $collectionResponse->toArray();
        $this->assertArrayHasKey('numberMatched', $responseArray);
        $this->assertGreaterThan(
            0, $responseArray['numberMatched']
        );
    }

    public function testGetCollectionExtentMatched(): void
    {
        $client = self::createClient();

        $collectionResponse = $this->apiRequest($client, 'GET', '/api/features/extent_matched/sites?name=sediment');
        $this->assertResponseStatusCodeSame(200);
        $responseArray = $collectionResponse->toArray();
        $this->assertArrayHasKey('extent', $responseArray);
        $this->assertIsArray($responseArray['extent']);
        $this->assertCount(4, $responseArray['extent']);
        $this->assertContainsOnlyFloat($responseArray['extent']);
        $this->assertArrayHasKey('crs', $responseArray);
        $this->assertArrayHasKey('properties', $responseArray['crs']);
        $this->assertArrayHasKey('name', $responseArray['crs']['properties']);
        $this->assertStringContainsString('EPSG::3857', $responseArray['crs']['properties']['name']);
    }
}
