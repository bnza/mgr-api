<?php

namespace App\Metadata\Attribute;

interface SubResourceFilterInterface
{
    public function getPrefix(): string;

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
