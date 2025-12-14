<?php

namespace App\State;

use ApiPlatform\Doctrine\Orm\Paginator;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Service\Geoserver\GeoserverXmlFilterBuilder;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

abstract class AbstractGeoserverFeatureCollectionProvider implements ProviderInterface
{
    public const string BASE_URL = 'http://geoserver:8080/geoserver/wfs';

    public function __construct(
        #[Autowire(service: 'api_platform.doctrine.orm.state.collection_provider')]
        protected readonly ProviderInterface $doctrineOrmCollectionProvider,
        #[Autowire(service: 'monolog.http_client')]
        protected readonly HttpClientInterface $httpClient,
        protected readonly GeoserverXmlFilterBuilder $xmlFilterBuilder,
    ) {
    }

    protected function getDefaultWfsParams(string $typeName): array
    {
        return [
            'service' => 'WFS',
            'version' => '2.0.0',
            'request' => 'GetFeature',
            'typeNames' => $typeName,
            'outputFormat' => 'application/json',
            'exceptions' => 'application/json',
        ];
    }

    /**
     * Retrieves the default values for the given operation.
     *
     * This method ensures the operation defaults include a valid 'typeName',
     * throwing an exception if it's missing or invalid. It returns an array
     * that contains the operation's 'typeName' and a geometric field name,
     * defaulting to 'the_geom' if not explicitly specified.
     *
     * @param Operation $operation the operation instance containing the default values
     *
     * @return array an array containing the operation's 'typeName' and geometric field
     *
     * @throws HttpException if the defaults are invalid or the 'typeName' is missing
     */
    protected function getOperationDefaults(Operation $operation): array
    {
        $defaults = $operation->getDefaults();
        if (!is_array($defaults) && !array_key_exists('typeName', $defaults)) {
            throw new HttpException(500, 'Invalid operation defaults, missing typeName');
        }

        return [
            $defaults['typeName'],
            $defaults['idField'] ?? 'id',
            $defaults['geomField'] ?? 'the_geom',
        ];
    }

    protected function getUnfilteredTotalItems(Operation $operation, array $uriVariables, array $context): int
    {
        $unfilteredContext = $context;
        $unfilteredContext['filters'] = [
            'page' => 1,
            'itemsPerPage' => 0,
        ];
        $unfilteredOperation = $operation->withPaginationEnabled(true);
        $unfilteredCollection = $this->doctrineOrmCollectionProvider->provide($unfilteredOperation, $uriVariables, $unfilteredContext);
        if (!$unfilteredCollection instanceof Paginator) {
            throw new HttpException(500, 'Invalid collection type');
        }

        return (int) $unfilteredCollection->getTotalItems();
    }

    protected function getIds(Operation $operation, array $uriVariables, array $context): ?array
    {
        $defaults = $operation->getDefaults();
        if (!is_array($defaults) && !array_key_exists('typeName', $defaults)) {
            throw new HttpException(500, 'Invalid operation defaults, missing typeName');
        }

        $collection = $this->doctrineOrmCollectionProvider->provide($operation, $uriVariables, $context);
        $unfilterTotalItems = $this->getUnfilteredTotalItems($operation, $uriVariables, $context);

        // Requested collection count match the unfiltered count, verbose id enumeration in unnecessary
        if ($unfilterTotalItems === count($collection)) {
            return null;
        }

        $ids = [];

        foreach ($collection as $item) {
            if (is_object($item) && method_exists($item, 'getId')) {
                $ids[] = $item->getId();
            }
        }

        return $ids;
    }

    protected function getRequestBbox($context = []): ?array
    {
        /** @var \Symfony\Component\HttpFoundation\Request $request */
        $request = $context['request'];
        $bbox = $request?->get('bbox');

        return $bbox ? explode(',', $bbox) : [];
    }

    protected function getResponseContent(ResponseInterface $response): string
    {
        if ($response->getStatusCode() < 400) {
            return $response->getContent(false);
        }
        $content = json_decode($response->getContent(false), true);
        throw new HttpException($response->getStatusCode(), $content['exceptions'][0]['text'] ?? 'Geoserver error');
    }

    protected function getQueryUrl(array $params): string
    {
        return self::BASE_URL.'?'.http_build_query($params, '', '&', PHP_QUERY_RFC3986);
    }
}
