<?php

namespace App\Tests\Functional\Api\Resource\Geoserver;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Tests\Functional\Api\ApiTestProviderTrait;
use App\Tests\Functional\ApiTestRequestTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class ApiResourceExportArchaoelogicalSiteTest extends ApiTestCase
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
        // $this->markTestSkipped('GeoServer relies on dev database.');
        $client = self::createClient();
        $token = $this->getUserToken($client, 'user_admin');

        $response = $this->apiRequest($client, 'GET', "/api/features/export/archaeological_sites?outputFormat=$outputFormat", [
            'token' => $token,
        ]);
        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseHeaderSame('content-type', $expectedContentType);
        $this->assertResponseHeaderSame('content-disposition', 'attachment; filename="archaeological_sites.'.self::getFileExtension($outputFormat).'"');
    }

    #[DataProvider('outputFormatProvider')]
    public function testExportFeatureCollectionFiltered(string $outputFormat, string $expectedContentType): void
    {
        // $this->markTestSkipped('GeoServer relies on dev database.');
        $client = self::createClient();
        $token = $this->getUserToken($client, 'user_admin');

        $response = $this->apiRequest($client, 'GET', "/api/features/export/archaeological_sites?name=medina&outputFormat=$outputFormat", [
            'token' => $token,
        ]);
        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseHeaderSame('content-type', $expectedContentType);
        $this->assertResponseHeaderSame('content-disposition', 'attachment; filename="archaeological_sites.'.self::getFileExtension($outputFormat).'"');
    }

    public function testExportFeatureCollectionDefaultFormat(): void
    {
        // $this->markTestSkipped('GeoServer relies on dev database.');
        $client = self::createClient();
        $token = $this->getUserToken($client, 'user_admin');

        $response = $this->apiRequest($client, 'GET', '/api/features/export/archaeological_sites', [
            'token' => $token,
        ]);
        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseHeaderSame('content-type', 'application/geo+json');
        $this->assertResponseHeaderSame('content-disposition', 'attachment; filename="archaeological_sites.geojson"');
    }

    public function testExportFeatureCollectionRequiresAuthentication(): void
    {
        $client = self::createClient();

        $this->apiRequest($client, 'GET', '/api/features/export/archaeological_sites');
        $this->assertResponseStatusCodeSame(401);
    }

    public function testExportFeatureCollectionInvalidFormat(): void
    {
        $client = self::createClient();
        $token = $this->getUserToken($client, 'user_admin');

        $this->apiRequest($client, 'GET', '/api/features/export/archaeological_sites?outputFormat=invalid', [
            'token' => $token,
        ]);
        $this->assertResponseStatusCodeSame(400);
    }

    public function testExportFeatureCollectionItemsMatchApiTotalItems(): void
    {
        $client = self::createClient();
        $token = $this->getUserToken($client, 'user_admin');

        // Get totalItems from the API collection endpoint
        $apiResponse = $this->apiRequest($client, 'GET', '/api/data/archaeological_sites?region.value=andalusia', [
            'token' => $token,
        ]);
        $this->assertResponseIsSuccessful();
        $totalItems = $apiResponse->toArray()['totalItems'];

        $this->assertGreaterThan(0, $totalItems, 'API totalItems should be greater than 0');

        // Get features count from the geojson export endpoint with the same filter
        $exportResponse = $this->apiRequest($client, 'GET', '/api/features/export/archaeological_sites?region.value=andalusia&outputFormat=geojson', [
            'token' => $token,
        ]);
        $this->assertResponseIsSuccessful();
        $exportData = $exportResponse->toArray();
        $this->assertNotNull($exportData, 'Export response should be valid JSON');
        $this->assertArrayHasKey('features', $exportData);
        $exportCount = count($exportData['features']);

        $this->assertSame($totalItems, $exportCount, 'Export geojson features count should match API totalItems');
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
