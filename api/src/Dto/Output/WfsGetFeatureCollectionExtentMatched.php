<?php

namespace App\Dto\Output;

use ApiPlatform\Metadata\ApiProperty;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Serializer\Annotation\Groups;

use function Symfony\Component\Clock\now;

readonly class WfsGetFeatureCollectionExtentMatched
{
    #[ApiProperty(
        required: true,
        openapiContext: [
            'type' => 'array',
            'items' => ['type' => 'number'],
            'minItems' => 4,
            'maxItems' => 4,
            'example' => [-574545.7563392848, 4371056.783165679, 58028.027939854575, 5020082.443572257],
        ]
    )]
    #[Groups(['wfs_extent_matched:read'])]
    public array $extent;

    #[ApiProperty(
        required: true,
        openapiContext: [
            'type' => 'object',
            'required' => ['type', 'properties'],
            'properties' => [
                'type' => ['type' => 'string', 'enum' => ['name']],
                'properties' => [
                    'type' => 'object',
                    'required' => ['name'],
                    'properties' => [
                        'name' => ['type' => 'string'],
                    ],
                ],
            ],
            'example' => [
                'type' => 'name',
                'properties' => [
                    'name' => 'urn:ogc:def:crs:EPSG::3857',
                ],
            ],
        ]
    )]
    #[Groups(['wfs_extent_matched:read'])]
    public array $crs;

    #[ApiProperty(
        required: true,
        openapiContext: [
            'type' => 'string',
            'format' => 'date-time',
        ]
    )]
    #[Groups(['wfs_extent_matched:read'])]
    public string $timeStamp;

    #[ApiProperty(
        required: true,
    )]
    #[Groups(['wfs_extent_matched:read'])]
    public string $typeName;

    public function __construct(string $typeName, ?string $response = null)
    {
        $this->typeName = $typeName;
        $this->timeStamp = now()->format(DATE_ATOM);
        if (null === $response || '' === $response) {
            $this->extent = [];
            $this->crs = [];

            return;
        }

        try {
            $xml = new \SimpleXMLElement($response);
            $namespaces = $xml->getNamespaces(true);

            if ('ExceptionReport' === $xml->getName()) {
                $owsNs = $namespaces['ows'] ?? 'http://www.opengis.net/ows/1.1';
                $exception = $xml->children($owsNs)->Exception;
                $code = (string) ($exception->attributes()['exceptionCode'] ?? 'UnknownError');
                $locator = (string) ($exception->attributes()['locator'] ?? '');
                $text = trim((string) $exception->ExceptionText);

                throw new HttpException(500, sprintf('Geoserver Error [%s]%s: %s', $code, $locator ? ' at '.$locator : '', $text));
            }

            $ows = $xml->children($namespaces['ows'] ?? 'http://www.opengis.net/ows/1.1');

            $lowerCorner = (string) $ows->LowerCorner;
            $upperCorner = (string) $ows->UpperCorner;

            if ($lowerCorner && $upperCorner) {
                $lower = explode(' ', trim($lowerCorner));
                $upper = explode(' ', trim($upperCorner));
                $this->extent = [
                    (float) $lower[0],
                    (float) $lower[1],
                    (float) $upper[0],
                    (float) $upper[1],
                ];
            } else {
                $this->extent = [];
            }

            $crsAttr = (string) ($xml->attributes()['crs'] ?? '');
            if ($crsAttr) {
                if (preg_match('/^EPSG:(\d+)$/i', $crsAttr, $matches)) {
                    $crsName = 'urn:ogc:def:crs:EPSG::'.$matches[1];
                } else {
                    $crsName = $crsAttr;
                }

                $this->crs = [
                    'type' => 'name',
                    'properties' => [
                        'name' => $crsName,
                    ],
                ];
            } else {
                $this->crs = [];
            }
        } catch (HttpException $e) {
            throw $e;
        } catch (\Throwable) {
            $this->extent = [];
            $this->crs = [];
        }
    }

    #[ApiProperty(required: true)]
    #[Groups(['wfs_extent_matched:read'])]
    public function getId(): string
    {
        return sprintf('%s:%s', $this->typeName, $this->timeStamp);
    }
}
