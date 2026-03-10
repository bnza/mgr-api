<?php

namespace App\State;

use ApiPlatform\Doctrine\Orm\Paginator;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Service\Geoserver\GeoserverXmlWfsGetFeatureFilterBuilder;
use App\Service\Geoserver\GeoserverXmlWpsExecuteBoundsBuilder;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

abstract class AbstractGeoserverFeatureCollectionProvider implements ProviderInterface
{
    public const string BASE_URL = 'http://geoserver:8080/geoserver/wfs';
    public const string WPS_URL = 'http://geoserver:8080/geoserver/wps';

    public function __construct(
        #[Autowire(service: 'api_platform.doctrine.orm.state.collection_provider')]
        protected readonly ProviderInterface $doctrineOrmCollectionProvider,
        #[Autowire(service: 'monolog.http_client')]
        protected readonly HttpClientInterface $httpClient,
        protected readonly GeoserverXmlWfsGetFeatureFilterBuilder $xmlFilterBuilder,
        protected readonly GeoserverXmlWpsExecuteBoundsBuilder $wpsBoundsBuilder,
        protected readonly PropertyAccessorInterface $propertyAccessor,
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
            $defaults['propertyNames'] ?? [],
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

    /**
     * Groups filtered entities by their spatial parent and returns a map of parent IDs to matched entity counts.
     *
     * Resolves the dot-notation accessor chain (e.g. 'stratigraphicUnit.site' → getStratigraphicUnit()->getSite()->getId())
     * on each entity to determine the spatial parent ID.
     *
     * @param Operation $operation      the current API operation
     * @param array     $uriVariables   URI variables
     * @param array     $context        request context
     * @param string    $parentAccessor dot-notation accessor chain to the spatial parent entity (e.g. 'stratigraphicUnit.site', 'location', 'site')
     *
     * @return array<int|string, int>|null a map of [parentId => matchedEntityCount], or null if all entities match (no filter needed)
     */
    protected function getParentIdCounts(Operation $operation, array $uriVariables, array $context, string $parentAccessor): ?array
    {
        $collection = $this->doctrineOrmCollectionProvider->provide($operation, $uriVariables, $context);
        $unfilteredTotalItems = $this->getUnfilteredTotalItems($operation, $uriVariables, $context);

        if ($unfilteredTotalItems === count($collection)) {
            return null;
        }

        $parentIdCounts = [];

        foreach ($collection as $item) {
            if (!is_object($item)) {
                continue;
            }

            try {
                $parentId = $this->propertyAccessor->getValue($item, $parentAccessor.'.id');
            } catch (\Exception) {
                $parentId = null;
            }

            if (null === $parentId) {
                continue;
            }
            $parentIdCounts[$parentId] = ($parentIdCounts[$parentId] ?? 0) + 1;
        }

        return $parentIdCounts;
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
