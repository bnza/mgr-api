<?php

namespace App\Metadata\Attribute\SubResourceFilters;

interface ApiSubResourceFiltersInterface
{
    /**
     * @return class-string The resource class this filter applies to.
     */
    public function getResourceClass(): string;

    /**
     * @return string The prefix of the filter name. Example: "analysis" will prepend "analysis." to all the filtered properties.
     */
    public function getPrefix(): string;

    /**
     * @return array<string>
     */
    public function getBooleanFields(): array;

    /**
     * @return array<string>
     */
    public function getExistsFields(): array;

    /**
     * @return array<string>
     */
    public function getRangeFields(): array;

    /**
     * @return array<string>
     */
    public function getSearchFields(): array;

    /**
     * @return array<string>
     */
    public function getUnaccentedSearchFields(): array;

    public function getIdSuffix(): ?string;
}
