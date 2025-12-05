<?php

namespace App\Metadata\Attribute\SubResourceFilters;

readonly class AbstractApiSubresourceFilters implements ApiSubResourceFiltersInterface
{
    protected function pascalToSnakeCase($string): string
    {
        // Insert underscore before uppercase letters (except the first letter)
        // Then convert the whole string to lowercase
        return strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $string));
    }

    public function getDefinitionId(string $resourceClass, ApiSubResourceFilterType $filterType, ?string $suffix): string
    {
        $filterClass = $this->pascalToSnakeCase(basename(str_replace('\\', '/', static::class))); // strtolower(str_replace('\\', '_', self::class));
        $base = self::pascalToSnakeCase(basename(str_replace('\\', '/', $resourceClass))); // strtolower(str_replace('\\', '_', $resourceClass));
        $suffix = $suffix ? preg_replace('~[^a-z0-9_]+~', '_', strtolower($suffix)).'_filter' : 'filter';

        return "annotated_{$base}_{$filterClass}_{$filterType->value}_{$suffix}";
    }

    public function __construct(
        private string $prefix,
        private array $rangeFields = [],
        private array $searchFields = [],
        private array $existsFields = [],
        private array $booleanFields = [],
        private array $unaccentedSearchFields = [],
        protected ?string $resourceClass = null,
        private ?string $idSuffix = null, // to disambiguate if needed
    ) {
    }

    public function getResourceClass(): string
    {
        return $this->resourceClass ?? static::class;
    }

    public function getPrefix(): string
    {
        return $this->prefix;
    }

    public function getBooleanFields(): array
    {
        return $this->booleanFields;
    }

    public function getExistsFields(): array
    {
        return $this->existsFields;
    }

    public function getSearchFields(): array
    {
        return $this->searchFields;
    }

    public function getRangeFields(): array
    {
        return $this->rangeFields;
    }

    public function getUnaccentedSearchFields(): array
    {
        return $this->unaccentedSearchFields;
    }

    public function getIdSuffix(): ?string
    {
        return $this->idSuffix;
    }
}
