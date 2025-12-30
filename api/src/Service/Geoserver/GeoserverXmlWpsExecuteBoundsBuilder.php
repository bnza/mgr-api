<?php

declare(strict_types=1);

namespace App\Service\Geoserver;

/**
 * Builds a WPS 1.0.0 Execute body for the vec:Bounds process.
 */
final class GeoserverXmlWpsExecuteBoundsBuilder
{
    /**
     * Build a WPS Execute XML for vec:Bounds.
     *
     * example XML:
     * ```xml
     * <?xml version="1.0" encoding="UTF-8" standalone="no"?>
     * <wps:Execute xmlns:wps="http://www.opengis.net/wps/1.0.0" xmlns:ows="http://www.opengis.net/ows/1.1" xmlns:xlink="http://www.w3.org/1999/xlink" service="WPS" version="1.0.0">
     *     <ows:Identifier>vec:Bounds</ows:Identifier>
     *     <wps:DataInputs>
     *         <wps:Input>
     *             <ows:Identifier>features</ows:Identifier>
     *             <wps:Reference method="POST" mimeType="text/xml" xlink:href="http://geoserver/wfs">
     *                 <wps:Body>
     *                     <wfs:GetFeature xmlns:fes="http://www.opengis.net/fes/2.0" xmlns:wfs="http://www.opengis.net/wfs/2.0" service="WFS" version="2.0.0">
     *                         <wfs:Query srsName="EPSG:3857" typeNames="mgr:history_locations">
     *                             <fes:Filter>
     *                                 <fes:Or>
     *                                     <fes:PropertyIsEqualTo>
     *                                         <fes:ValueReference>id</fes:ValueReference>
     *                                         <fes:Literal>7</fes:Literal>
     *                                     </fes:PropertyIsEqualTo>
     *                                     <fes:PropertyIsEqualTo>
     *                                         <fes:ValueReference>id</fes:ValueReference>
     *                                         <fes:Literal>8</fes:Literal>
     *                                     </fes:PropertyIsEqualTo>
     *                                 </fes:Or>
     *                             </fes:Filter>
     *                         </wfs:Query>
     *                     </wfs:GetFeature>
     *                 </wps:Body>
     *             </wps:Reference>
     *         </wps:Input>
     *     </wps:DataInputs>
     *     <wps:ResponseForm>
     *         <wps:RawDataOutput>
     *             <ows:Identifier>bounds</ows:Identifier>
     *         </wps:RawDataOutput>
     *     </wps:ResponseForm>
     * </wps:Execute>
     * ```
     *
     * @param string         $typeName        GeoServer type name, e.g. "mgr:history_locations"
     * @param int[]|string[] $ids             List of feature IDs to include (OR-ed). Empty for no filter.
     * @param string         $srsName         SRS name for the Query (default: "EPSG:3857")
     * @param string         $idField         Name of the ID property in the feature type (default: "id")
     * @param string         $geoserverWfsUrl The URL for Geoserver WFS (default: "http://geoserver/wfs")
     *
     * @throws \DOMException
     */
    public function buildExecuteBounds(
        string $typeName,
        array $ids = [],
        string $srsName = 'EPSG:3857',
        string $idField = 'id',
        string $geoserverWfsUrl = 'http://geoserver/wfs',
        bool $prettyPrint = false,
    ): string {
        $doc = new \DOMDocument('1.0', 'UTF-8');
        $doc->formatOutput = $prettyPrint;

        $nsWps = 'http://www.opengis.net/wps/1.0.0';
        $nsOws = 'http://www.opengis.net/ows/1.1';
        $nsXlink = 'http://www.w3.org/1999/xlink';
        $nsWfs = 'http://www.opengis.net/wfs/2.0';
        $nsFes = 'http://www.opengis.net/fes/2.0';

        // <wps:Execute version="1.0.0" service="WPS" ...>
        $execute = $doc->createElementNS($nsWps, 'wps:Execute');
        $execute->setAttribute('version', '1.0.0');
        $execute->setAttribute('service', 'WPS');
        $execute->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:ows', $nsOws);
        $execute->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:xlink', $nsXlink);
        $doc->appendChild($execute);

        // <ows:Identifier>vec:Bounds</ows:Identifier>
        $execute->appendChild($doc->createElementNS($nsOws, 'ows:Identifier', 'vec:Bounds'));

        // <wps:DataInputs>
        $dataInputs = $doc->createElementNS($nsWps, 'wps:DataInputs');
        $execute->appendChild($dataInputs);

        // <wps:Input>
        $input = $doc->createElementNS($nsWps, 'wps:Input');
        $dataInputs->appendChild($input);

        // <ows:Identifier>features</ows:Identifier>
        $input->appendChild($doc->createElementNS($nsOws, 'ows:Identifier', 'features'));

        // <wps:Reference mimeType="text/xml" method="POST" xlink:href="...">
        $reference = $doc->createElementNS($nsWps, 'wps:Reference');
        $reference->setAttribute('mimeType', 'text/xml');
        $reference->setAttribute('method', 'POST');
        $reference->setAttributeNS($nsXlink, 'xlink:href', $geoserverWfsUrl);
        $input->appendChild($reference);

        // <wps:Body>
        $body = $doc->createElementNS($nsWps, 'wps:Body');
        $reference->appendChild($body);

        // Build WFS GetFeature XML
        $getFeature = $doc->createElementNS($nsWfs, 'wfs:GetFeature');
        $getFeature->setAttribute('service', 'WFS');
        $getFeature->setAttribute('version', '2.0.0');
        $getFeature->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:wfs', $nsWfs);
        $getFeature->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:fes', $nsFes);
        $body->appendChild($getFeature);

        // <wfs:Query typeNames="..." srsName="...">
        $query = $doc->createElementNS($nsWfs, 'wfs:Query');
        $query->setAttribute('typeNames', $typeName);
        $query->setAttribute('srsName', $srsName);
        $getFeature->appendChild($query);

        // Build FES Filter parts depending on inputs
        $filterParts = [];

        // IDs OR group
        $ids = array_values(array_filter($ids, static fn ($v) => null !== $v && '' !== $v));
        if (!empty($ids)) {
            $or = $doc->createElementNS($nsFes, 'fes:Or');
            foreach ($ids as $id) {
                $cmp = $doc->createElementNS($nsFes, 'fes:PropertyIsEqualTo');

                $valRef = $doc->createElementNS($nsFes, 'fes:ValueReference', $idField);
                $cmp->appendChild($valRef);

                $literal = $doc->createElementNS($nsFes, 'fes:Literal', (string) $id);
                $cmp->appendChild($literal);

                $or->appendChild($cmp);
            }
            $filterParts[] = $or;
        }

        // If we have any filters, wrap appropriately
        if (!empty($filterParts)) {
            $filter = $doc->createElementNS($nsFes, 'fes:Filter');

            if (1 === count($filterParts)) {
                $filter->appendChild($filterParts[0]);
            } else {
                $and = $doc->createElementNS($nsFes, 'fes:And');
                foreach ($filterParts as $part) {
                    $and->appendChild($part);
                }
                $filter->appendChild($and);
            }

            $query->appendChild($filter);
        }

        // <wps:ResponseForm>
        $responseForm = $doc->createElementNS($nsWps, 'wps:ResponseForm');
        $execute->appendChild($responseForm);

        // <wps:RawDataOutput>
        $rawDataOutput = $doc->createElementNS($nsWps, 'wps:RawDataOutput');
        $responseForm->appendChild($rawDataOutput);

        // <ows:Identifier>bounds</ows:Identifier>
        $rawDataOutput->appendChild($doc->createElementNS($nsOws, 'ows:Identifier', 'bounds'));

        return $doc->saveXML();
    }
}
