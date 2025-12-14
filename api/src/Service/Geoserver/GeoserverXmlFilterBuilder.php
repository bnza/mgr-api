<?php

declare(strict_types=1);

namespace App\Service\Geoserver;

/**
 * Builds a WFS 2.0 POST body with an FES 2.0 Filter suitable for GeoServer
 * that requests GeoJSON and filters by a list of IDs and an optional BBOX.
 */
final class GeoserverXmlFilterBuilder
{
    private function getBaseDocument(): \DOMDocument
    {
        return new \DOMDocument('1.0', 'UTF-8');
    }

    /**
     * Build a WFS GetFeature XML with an FES filter.
     *
     * @param int[]|string[] $ids       List of feature IDs to include (OR-ed). Empty to skip the ID filter.
     * @param array|null     $bbox      [minX, minY, maxX, maxY, srs?] (srs optional; defaults to EPSG:4326 urn)
     * @param string         $typeName  GeoServer type name, e.g. "mgr:history_locations"
     * @param string         $idField   Name of the ID property in the feature type (default: "id")
     * @param string         $geomField Geometry property name (default: "the_geom")
     * @param ?string        $srsUrn    Default SRS URN for the Envelope when bbox[4] is not provided
     */
    public function buildGetFeature(
        string $typeName,
        array $ids = [],
        ?array $bbox = null,
        ?string $idField = 'id',
        ?string $geomField = 'the_geom',
        ?string $srsUrn = 'urn:ogc:def:crs:EPSG::4326',
        bool $prettyPrint = false,
    ): string {
        $doc = new \DOMDocument('1.0', 'UTF-8');
        $doc->formatOutput = $prettyPrint;

        // Namespaces
        $nsWfs = 'http://www.opengis.net/wfs/2.0';
        $nsFes = 'http://www.opengis.net/fes/2.0';
        $nsGml = 'http://www.opengis.net/gml/3.2';
        // $nsMgr = 'http://mgr'; // adjust to your schema namespace if needed

        // <wfs:GetFeature ...>
        $getFeature = $doc->createElementNS($nsWfs, 'wfs:GetFeature');
        $getFeature->setAttribute('service', 'WFS');
        $getFeature->setAttribute('version', '2.0.0');
        $getFeature->setAttribute('outputFormat', 'application/json');
        // declare other namespaces on the root element
        $getFeature->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:fes', $nsFes);
        $getFeature->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:gml', $nsGml);
        //        $getFeature->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:mgr', $nsMgr);
        $doc->appendChild($getFeature);

        // <wfs:Query typeNames="mgr:history_locations">
        $query = $doc->createElementNS($nsWfs, 'wfs:Query');
        $query->setAttribute('typeNames', $typeName);
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

        // BBOX filter
        if (is_array($bbox) && count($bbox) >= 4) {
            [$minX, $minY, $maxX, $maxY] = array_map('strval', array_slice($bbox, 0, 4));
            $srsName = isset($bbox[4]) && is_string($bbox[4]) && '' !== $bbox[4]
                ? self::normalizeSrs($bbox[4])
                : $srsUrn;

            $bboxEl = $doc->createElementNS($nsFes, 'fes:BBOX');

            $valRef = $doc->createElementNS($nsFes, 'fes:ValueReference', $geomField);
            $bboxEl->appendChild($valRef);

            $env = $doc->createElementNS($nsGml, 'gml:Envelope');
            $env->setAttribute('srsName', $srsName);
            $lower = $doc->createElementNS($nsGml, 'gml:lowerCorner', $minX.' '.$minY);
            $upper = $doc->createElementNS($nsGml, 'gml:upperCorner', $maxX.' '.$maxY);
            $env->appendChild($lower);
            $env->appendChild($upper);

            $bboxEl->appendChild($env);
            $filterParts[] = $bboxEl;
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

        return $doc->saveXML();
    }

    private static function normalizeSrs(string $srs): string
    {
        $srs = trim($srs);
        // Accept common forms and normalize to URN expected by many WFS 2.0 implementations
        if (preg_match('~^EPSG:(\d+)$~i', $srs, $m)) {
            return 'urn:ogc:def:crs:EPSG::'.$m[1];
        }
        if (preg_match('~^urn:ogc:def:crs:EPSG::\d+$~i', $srs)) {
            return $srs;
        }

        // Fallback as-is
        return $srs;
    }
}
