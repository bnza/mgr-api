<?php

namespace App\DependencyInjection\Compiler;

use ApiPlatform\Metadata\FilterInterface;
use ApiPlatform\Metadata\Util\ReflectionClassRecursiveIterator;
use App\Metadata\Attribute\SubResourceFilters\ApiSubResourceFiltersInterface;
use App\Metadata\Attribute\SubResourceFilters\ApiSubResourceFilterType;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

/**
 * Base compiler pass used to register concrete Api Platform filter services from a
 * single custom "subresource filters" attribute applied to resource classes.
 *
 * How it works
 * - Scans Api Platform resource class directories for classes annotated with a concrete
 *   implementation of {@see ApiSubResourceFiltersInterface} (provided by subclasses via {@see getFiltersClass()}).
 * - For each attribute occurrence, it builds the list of filter properties per filter type
 *   (Search, Range, Exists, UnaccentedSearch) using either defaults defined on the compiler pass
 *   or overrides provided by the attribute.
 * - It then registers dedicated, autowired filter services (tagged with `api_platform.filter`) and
 *   injects the computed `$properties` argument for each one.
 *
 * Notes
 * - Range/Exists filters accept a list of property names; Search/UnaccentedSearch expect
 *   an associative array of `property => strategy`. This pass normalizes list-shaped arrays
 *   to the associative form `property => null` where appropriate so constructors are satisfied.
 * - This pass uses Api Platform's {@see ReflectionClassRecursiveIterator} to discover resource
 *   classes. That utility is marked `@internal` upstream; if it changes in a future version, consider
 *   replacing it with a Symfony Finder + Reflection fallback.
 */
abstract class AbstractSubresourceFiltersCompilerPass implements CompilerPassInterface
{
    /**
     * Default properties for BooleanFilter.
     *
     * Expected shape:
     * - either a list of property names: list<string>
     * - or an associative map where values are ignored: array<string, null>
     *
     * Subclasses should override as needed.
     *
     * @var list<string>|array<string, null>
     */
    protected array $defaultBooleanProps = [];

    /**
     * Default properties for ExistsFilter.
     *
     * Expected shape:
     * - either a list of property names: list<string>
     * - or an associative map where values are ignored: array<string, null>
     *
     * Subclasses should override as needed.
     *
     * @var list<string>|array<string, null>
     */
    protected array $defaultExistsProps = [];

    /**
     * Default properties for RangeFilter.
     *
     * Expected shape:
     * - list of property names: list<string>
     *
     * Subclasses should override as needed.
     *
     * @var list<string>
     */
    protected array $defaultRangeProps = [];

    /**
     * Default properties for SearchFilter.
     *
     * Expected shape:
     * - associative map of `property => strategy` (e.g. 'exact', 'ipartial'): array<string, string>
     *
     * Subclasses should override as needed.
     *
     * @var array<string, string>
     */
    protected array $defaultSearchProps = [];

    /**
     * Default properties for UnaccentedSearchFilter (custom filter).
     *
     * Expected shape:
     * - associative map of `property => strategy` (e.g. 'partial'): array<string, string>
     *
     * Subclasses should override as needed.
     *
     * @var array<string, string>
     */
    protected array $defaultUnaccentedSearchProps = [];

    /**
     * Local cache of computed, fully-qualified filter properties keyed by prefix.
     *
     * @var array<string, array<string, array<string, string|null>>> prefix => [type => [property => strategy|null]]
     */
    private array $filterProps = [];

    /**
     * Returns the attribute class that drives the filter registration for a given family
     * of subresource filters.
     *
     * Example: returns {@see \App\Metadata\Attribute\SubResourceFilters\ApiStratigraphicUnitSubresourceFilters::class}.
     *
     * @return class-string<ApiSubResourceFiltersInterface>
     */
    abstract protected function getFiltersMetadataClass(): string;

    /**
     * Converts snake_case to PascalCase.
     *
     * Example: "unaccented_search" => "UnaccentedSearch".
     */
    private function snakeToPascalCase(string $string): string
    {
        return str_replace('_', '', ucwords($string, '_')); // str_replace(' ', '', ucwords(str_replace('_', ' ', $str)));
    }

    /**
     * Resolves the concrete filter class FQCN for a given filter type.
     *
     * Built-ins (Search/Range/Exists) use Api Platform ORM filters, while the custom
     * UnaccentedSearch uses the app's own filter namespace.
     *
     * @return class-string<FilterInterface>
     */
    private function getFilterClass(ApiSubResourceFilterType $type): string
    {
        $namespace = $type->name === ApiSubResourceFilterType::UNACCENTED_SEARCH->name ? 'App\Doctrine\Filter' : 'ApiPlatform\Doctrine\Orm\Filter';

        return sprintf('%s\%sFilter', $namespace, $this->snakeToPascalCase($type->value));
    }

    /**
     * Computes the per-type, fully-qualified filter properties for the given attribute
     * configuration, applying the declared prefix and merging defaults with overrides.
     *
     * Shapes normalization
     * - If a property list is provided (numeric keys), it is converted to an associative
     *   form of `property => null` to satisfy filter constructors expecting maps.
     * - Properties are prefixed (e.g. "subject.su" + "year" => "subject.su.year").
     *
     * @return array<string, array<string, string|null>> keyed by filter type value
     */
    protected function getFiltersProps(ApiSubResourceFiltersInterface $cfg): array
    {
        $prefix = rtrim($cfg->getPrefix(), '.');
        $key = md5(get_class($cfg).$prefix);
        if (!array_key_exists($key, $this->filterProps)) {
            $filterProps = [];
            foreach (ApiSubResourceFilterType::cases() as $type) {
                $pascalCase = $this->snakeToPascalCase($type->value);
                $defaultPropName = "default{$pascalCase}Props"; // e.g. defaultSearchProps
                /** @var array<string, mixed> $defaultProps */
                $defaultProps = $this->{$defaultPropName};
                $configPropGetter = "get{$pascalCase}Fields"; // e.g. getSearchFields
                $configProps = $cfg->{$configPropGetter}();
                $props = $configProps ?: $defaultProps;

                if (empty($props)) {
                    continue;
                }
                // Normalize list-shaped arrays to [property => null]
                if (array_is_list($props)) {
                    $props = array_fill_keys($props, null);
                }

                $format = fn ($prop) => implode('.', array_filter([$prefix, $prop]));
                $return = [];
                foreach ($props as $prop => $value) {
                    $return[$format($prop)] = $value;
                }
                $filterProps[$type->value] = $return;
            }
            $this->filterProps[$key] = $filterProps;
        }

        return $this->filterProps[$key];
    }

    /**
     * Symfony DI compiler pass entry point.
     *
     * Registers one filter service per filter type for each resource class carrying
     * the configured subresource attribute. Services are tagged as `api_platform.filter`
     * so they are picked up by Api Platform's filter locator.
     */
    public function process(ContainerBuilder $container): void
    {
        $dirs = $container->getParameter('api_platform.resource_class_directories');
        foreach (ReflectionClassRecursiveIterator::getReflectionClassesFromDirectories($dirs) as $resourceClass => $refl) {
            $filtersClass = $this->getFiltersMetadataClass();
            foreach ($refl->getAttributes($filtersClass) as $attr) {
                /** @var ApiSubResourceFiltersInterface $apiSubResourceFilters */
                $apiSubResourceFilters = $attr->newInstance();
                foreach ($this->getFiltersProps($apiSubResourceFilters) as $type => $props) {
                    $enumType = ApiSubResourceFilterType::from($type);
                    $containerId = $apiSubResourceFilters->getDefinitionId($resourceClass, $enumType, $apiSubResourceFilters->getIdSuffix());
                    if (!$container->has($containerId)) {
                        $filterClass = $this->getFilterClass($enumType);
                        $def = new Definition($filterClass);
                        $def
                            ->setAutowired(true)
                            ->addTag('api_platform.filter')
                            ->setArgument('$properties', $props);
                        $container->setDefinition($containerId, $def);
                    }
                }
            }
        }
    }
}
