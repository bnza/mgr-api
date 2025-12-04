<?php

namespace App\Metadata\Attribute;

readonly class AbstractSubresourceFilters implements SubResourceFilterInterface
{
    public static function getDefinitionId(string $resourceClass, SubResourceFilterType $filterType, ?string $suffix): string
    {
        $filterClass = strtolower(str_replace('\\', '_', self::class));
        $base = strtolower(str_replace('\\', '_', $resourceClass));
        $suffix = $suffix ? preg_replace('~[^a-z0-9_]+~', '_', strtolower($suffix)).'_filter' : 'filter';

        return "annotated_{$base}_{$filterClass}_{$filterType->value}_{$suffix}";
    }

    public function __construct(
        private string $prefix,
        private array $rangeFields = [],
        private array $searchFields = [],
        private array $existsFields = [],
        private array $unaccentedSearchFields = [],
        private ?string $idSuffix = null, // to disambiguate if needed
    ) {
    }

    public function getPrefix(): string
    {
        return $this->prefix;
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
