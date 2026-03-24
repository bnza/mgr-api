<?php

namespace App\Tests\Functional\Api\Resource\Geoserver;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Tests\Functional\Api\ApiTestProviderTrait;
use App\Tests\Functional\ApiTestRequestTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class ApiResourcePaleoclimateSamplingSiteGeoserverTest extends ApiTestCase
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

        $collectionResponse = $this->apiRequest($client, 'GET', '/api/features/paleoclimate_sampling_sites?bbox=477474.3708727881,3803391.521162848,3814213.6816450283,5703600.6934222365,EPSG:3857', [
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

        $collectionResponse = $this->apiRequest($client, 'GET', '/api/features/paleoclimate_sampling_sites?name=cueva&bbox=477474.3708727881,3803391.521162848,3814213.6816450283,5703600.6934222365,EPSG:3857', [
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

        $collectionResponse = $this->apiRequest($client, 'GET', '/api/features/paleoclimate_sampling_sites?bbox=477474.3708727881,3803391.521162848,3814213.6816450283,5703600.6934222365,EPSG:3857', [
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

        $collectionResponse = $this->apiRequest($client, 'GET', '/api/features/paleoclimate_sampling_sites?name=cueva&bbox=477474.3708727881,3803391.521162848,3814213.6816450283,5703600.6934222365,EPSG:3857', [
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

        $collectionResponse = $this->apiRequest($client, 'GET', '/api/features/number_matched/paleoclimate_sampling_sites?bbox=477474.3708727881,3803391.521162848,3814213.6816450283,5703600.6934222365,EPSG:3857');
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

        $collectionResponse = $this->apiRequest($client, 'GET', '/api/features/number_matched/paleoclimate_sampling_sites?name=cueva&bbox=477474.3708727881,3803391.521162848,3814213.6816450283,5703600.6934222365,EPSG:3857');
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

        $collectionResponse = $this->apiRequest($client, 'GET', '/api/features/extent_matched/paleoclimate_sampling_sites?name=cueva');
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

    public static function outputFormatProvider(): array
    {
        return [
            'geojson' => ['geojson', 'application/geo+json'],
            'shapefile' => ['shapefile', 'application/zip'],
            'csv' => ['csv', 'text/csv; charset=UTF-8'],
            'kml' => ['kml', 'application/vnd.google-earth.kml+xml'],
            'gml3' => ['gml3', 'application/xml'],
        ];
    }

    #[DataProvider('outputFormatProvider')]
    public function testExportFeatureCollection(string $outputFormat, string $expectedContentType): void
    {
        $client = self::createClient();
        $token = $this->getUserToken($client, 'user_admin');

        $response = $this->apiRequest($client, 'GET', "/api/features/export/paleoclimate_sampling_sites?outputFormat=$outputFormat", [
            'token' => $token,
        ]);
        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseHeaderSame('content-type', $expectedContentType);
        $this->assertResponseHeaderSame('content-disposition', 'attachment; filename="paleoclimate_sampling_sites.'.self::getFileExtension($outputFormat).'"');
    }

    #[DataProvider('outputFormatProvider')]
    public function testExportFeatureCollectionFiltered(string $outputFormat, string $expectedContentType): void
    {
        $client = self::createClient();
        $token = $this->getUserToken($client, 'user_admin');

        $response = $this->apiRequest($client, 'GET', "/api/features/export/paleoclimate_sampling_sites?name=cueva&outputFormat=$outputFormat", [
            'token' => $token,
        ]);
        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseHeaderSame('content-type', $expectedContentType);
        $this->assertResponseHeaderSame('content-disposition', 'attachment; filename="paleoclimate_sampling_sites.'.self::getFileExtension($outputFormat).'"');
    }

    public function testExportFeatureCollectionDefaultFormat(): void
    {
        $client = self::createClient();
        $token = $this->getUserToken($client, 'user_admin');

        $response = $this->apiRequest($client, 'GET', '/api/features/export/paleoclimate_sampling_sites', [
            'token' => $token,
        ]);
        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseHeaderSame('content-type', 'application/geo+json');
        $this->assertResponseHeaderSame('content-disposition', 'attachment; filename="paleoclimate_sampling_sites.geojson"');
    }

    public function testExportFeatureCollectionRequiresAuthentication(): void
    {
        $client = self::createClient();

        $this->apiRequest($client, 'GET', '/api/features/export/paleoclimate_sampling_sites');
        $this->assertResponseStatusCodeSame(401);
    }

    public function testExportFeatureCollectionInvalidFormat(): void
    {
        $client = self::createClient();
        $token = $this->getUserToken($client, 'user_admin');

        $this->apiRequest($client, 'GET', '/api/features/export/paleoclimate_sampling_sites?outputFormat=invalid', [
            'token' => $token,
        ]);
        $this->assertResponseStatusCodeSame(400);
    }

    private static function getFileExtension(string $formatAlias): string
    {
        return match ($formatAlias) {
            'geojson' => 'geojson',
            'shapefile' => 'zip',
            'csv' => 'csv',
            'kml' => 'kml',
            'gml3' => 'gml',
            default => 'bin',
        };
    }
}
