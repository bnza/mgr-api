<?php

namespace App\DependencyInjection\Compiler;

use App\Metadata\Attribute\SubResourceFilters\ApiStratigraphicUnitSubresourceFilters;

final class StratigraphicUnitFiltersCompilerPass extends AbstractSubresourceFiltersCompilerPass
{
    protected array $defaultExistsProps = [
        'description',
        'interpretation',
        'chronologyLower',
        'chronologyUpper',
    ];

    protected array $defaultSearchProps = [
        'area' => 'exact',
        'building' => 'exact',
        'chronologyLower' => 'exact',
        'chronologyUpper' => 'exact',
        'number' => 'exact',
        'site' => 'exact',
        'year' => 'exact',
    ];

    protected array $defaultRangeProps = [
        'chronologyLower',
        'chronologyUpper',
        'number',
        'year',
    ];

    protected array $defaultUnaccentedSearchProps = [
        'description' => 'partial',
        'interpretation' => 'partial',
    ];

    protected function getFiltersMetadataClass(): string
    {
        return ApiStratigraphicUnitSubresourceFilters::class;
    }
}
