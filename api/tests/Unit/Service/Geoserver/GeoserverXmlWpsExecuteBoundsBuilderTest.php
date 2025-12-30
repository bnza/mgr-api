<?php

namespace App\Tests\Unit\Service\Geoserver;

use App\Service\Geoserver\GeoserverXmlWpsExecuteBoundsBuilder;
use PHPUnit\Framework\TestCase;

class GeoserverXmlWpsExecuteBoundsBuilderTest extends TestCase
{
    private GeoserverXmlWpsExecuteBoundsBuilder $builder;

    protected function setUp(): void
    {
        $this->builder = new GeoserverXmlWpsExecuteBoundsBuilder();
    }

    public function testBuildExecuteBoundsProducesCorrectXml(): void
    {
        $typeName = 'mgr:history_locations';
        $ids = [123];
        $idField = 'id';
        $srsName = 'EPSG:3857';
        $geoserverWfsUrl = 'http://geoserver/wfs';

        $xml = $this->builder->buildXmlBody($typeName, $ids, $srsName, $idField, $geoserverWfsUrl, true);

        $this->assertStringContainsString('<?xml version="1.0" encoding="UTF-8"?>', $xml);
        $this->assertStringContainsString('<wps:Execute', $xml);
        $this->assertStringContainsString('version="1.0.0"', $xml);
        $this->assertStringContainsString('service="WPS"', $xml);
        $this->assertStringContainsString('xmlns:wps="http://www.opengis.net/wps/1.0.0"', $xml);
        $this->assertStringContainsString('xmlns:ows="http://www.opengis.net/ows/1.1"', $xml);
        $this->assertStringContainsString('xmlns:xlink="http://www.w3.org/1999/xlink"', $xml);
        $this->assertStringContainsString('<ows:Identifier>vec:Bounds</ows:Identifier>', $xml);
        $this->assertStringContainsString('<wps:DataInputs>', $xml);
        $this->assertStringContainsString('<wps:Input>', $xml);
        $this->assertStringContainsString('<ows:Identifier>features</ows:Identifier>', $xml);
        $this->assertStringContainsString('<wps:Reference', $xml);
        $this->assertStringContainsString('mimeType="text/xml"', $xml);
        $this->assertStringContainsString('method="POST"', $xml);
        $this->assertStringContainsString('xlink:href="http://geoserver/wfs"', $xml);
        $this->assertStringContainsString('<wps:Body>', $xml);
        $this->assertStringContainsString('<wfs:GetFeature', $xml);
        $this->assertStringContainsString('service="WFS"', $xml);
        $this->assertStringContainsString('version="2.0.0"', $xml);
        $this->assertStringContainsString('xmlns:wfs="http://www.opengis.net/wfs/2.0"', $xml);
        $this->assertStringContainsString('xmlns:fes="http://www.opengis.net/fes/2.0"', $xml);
        $this->assertStringContainsString('<wfs:Query typeNames="mgr:history_locations" srsName="EPSG:3857">', $xml);
        $this->assertStringContainsString('<fes:Filter', $xml);
        $this->assertStringContainsString('<fes:PropertyIsEqualTo>', $xml);
        $this->assertStringContainsString('<fes:ValueReference>id</fes:ValueReference>', $xml);
        $this->assertStringContainsString('<fes:Literal>123</fes:Literal>', $xml);
        $this->assertStringContainsString('<wps:ResponseForm>', $xml);
        $this->assertStringContainsString('<wps:RawDataOutput>', $xml);
        $this->assertStringContainsString('<ows:Identifier>bounds</ows:Identifier>', $xml);
    }

    public function testBuildExecuteBoundsWithMultipleIds(): void
    {
        $typeName = 'mgr:history_locations';
        $ids = [123, 456];
        $xml = $this->builder->buildXmlBody($typeName, $ids, 'EPSG:3857', 'id', 'http://geoserver/wfs', true);

        $this->assertStringContainsString('<fes:Filter', $xml);
        $this->assertStringContainsString('<fes:Or>', $xml);
        $this->assertStringContainsString('<fes:PropertyIsEqualTo>', $xml);
        $this->assertStringContainsString('<fes:ValueReference>id</fes:ValueReference>', $xml);
        $this->assertStringContainsString('<fes:Literal>123</fes:Literal>', $xml);
        $this->assertStringContainsString('<fes:Literal>456</fes:Literal>', $xml);
    }

    public function testBuildExecuteBoundsWithNoIds(): void
    {
        $typeName = 'mgr:history_locations';
        $xml = $this->builder->buildXmlBody($typeName, [], 'EPSG:3857', 'id', 'http://geoserver/wfs', true);

        $this->assertStringNotContainsString('<fes:Filter>', $xml);
    }
}
