<?php

namespace App\Tests\Functional\Api\Resource\Geoserver;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Tests\Functional\Api\ApiTestProviderTrait;
use App\Tests\Functional\ApiTestRequestTrait;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class ApiResourceHistoryLocationTest extends ApiTestCase
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

    public function testGetCollectionWithHeaders(): void
    {
        $client = self::createClient();

        $collectionResponse = $this->apiRequest($client, 'GET', '/api/features/history/locations?search=castillo&bbox=477474.3708727881,3803391.521162848,3814213.6816450283,5703600.6934222365,EPSG:3857', [
            'headers' => [
                'Accept' => 'application/geo+json',
            ],
        ]);
        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseHeaderSame('content-type', 'application/geo+json; charset=utf-8');
    }

    //    public function testGetCollectionWithUrlFormat(): void
    //    {
    //        $client = self::createClient();
    //
    //        $collectionResponse = $this->apiRequest($client, 'GET', '/api/features/history/locations.geojson?search=castillo');
    //        $this->assertResponseStatusCodeSame(200);
    //        $this->assertResponseHeaderSame('content-type', 'application/geo+json; charset=utf-8');
    //    }
}
