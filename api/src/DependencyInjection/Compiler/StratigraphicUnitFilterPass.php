<?php

namespace App\DependencyInjection\Compiler;

use App\Metadata\Attribute\ApiStratigraphicUnitSubresourceFilters;

final class StratigraphicUnitFilterPass extends AbstractSubresourceFilterClass
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

    protected function getFiltersClass(): string
    {
        return ApiStratigraphicUnitSubresourceFilters::class;
    }
}
