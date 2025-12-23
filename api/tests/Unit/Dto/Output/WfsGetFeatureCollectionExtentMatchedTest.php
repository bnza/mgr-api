<?php

namespace App\Tests\Unit\Dto\Output;

use App\Dto\Output\WfsGetFeatureCollectionExtentMatched;
use PHPUnit\Framework\TestCase;

class WfsGetFeatureCollectionExtentMatchedTest extends TestCase
{
    public function testParseXmlResponse(): void
    {
        $xmlResponse = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<ows:BoundingBox xmlns:xs="http://www.w3.org/2001/XMLSchema" xmlns:ows="http://www.opengis.net/ows/1.1" crs="EPSG:3857">
    <ows:LowerCorner>-18238.77364863145 4882389.787301353</ows:LowerCorner>
    <ows:UpperCorner>45765.683028112675 4932493.340913888</ows:UpperCorner>
</ows:BoundingBox>
XML;

        $dto = new WfsGetFeatureCollectionExtentMatched('mgr:history_locations', $xmlResponse);

        $this->assertEquals([-18238.77364863145, 4882389.787301353, 45765.683028112675, 4932493.340913888], $dto->extent);
        $this->assertEquals([
            'type' => 'name',
            'properties' => [
                'name' => 'urn:ogc:def:crs:EPSG::3857',
            ],
        ], $dto->crs);
        $this->assertEquals('mgr:history_locations', $dto->typeName);
        $this->assertNotEmpty($dto->timeStamp);
    }

    public function testParseEmptyResponse(): void
    {
        $dto = new WfsGetFeatureCollectionExtentMatched('mgr:history_locations', null);

        $this->assertEquals([], $dto->extent);
        $this->assertEquals([], $dto->crs);
    }

    public function testParseExceptionReport(): void
    {
        $xmlResponse = <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="no"?>
<ows:ExceptionReport xmlns:ows="http://www.opengis.net/ows/1.1" xmlns:xs="http://www.w3.org/2001/XMLSchema" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" version="1.1.0" xsi:schemaLocation="http://www.opengis.net/ows/1.1 http://geoserver:8080/geoserver/schemas/ows/1.1.0/owsAll.xsd">
    <ows:Exception exceptionCode="InvalidParameterValue" locator="features">
        <ows:ExceptionText>Invalid input: features
Evaluation Failure: 'http://wrong/wfs' was not accepted by external URL checks</ows:ExceptionText>
    </ows:Exception>
</ows:ExceptionReport>
XML;

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        // We will define the exact message in the implementation
        $this->expectExceptionMessage('InvalidParameterValue');

        new WfsGetFeatureCollectionExtentMatched('mgr:history_locations', $xmlResponse);
    }
}
