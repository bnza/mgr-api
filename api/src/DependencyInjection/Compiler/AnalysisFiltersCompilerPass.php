<?php

namespace App\DependencyInjection\Compiler;

use App\Metadata\Attribute\SubResourceFilters\ApiAnalysisSubresourceFilters;

final class AnalysisFiltersCompilerPass extends AbstractSubresourceFiltersCompilerPass
{
    protected array $defaultExistsProps = [
        'laboratory',
        'responsible',
        'summary',
    ];

    protected array $defaultSearchProps = [
        'createdBy.email' => 'exact',
        'identifier' => 'ipartial',
        'laboratory' => 'ipartial',
        'responsible' => 'ipartial',
        'status' => 'exact',
        'type' => 'exact',
        'type.code' => 'exact',
        'type.group' => 'exact',
        'year' => 'exact',
    ];

    protected array $defaultRangeProps = [
        'year',
    ];

    protected array $defaultUnaccentedSearchProps = [
        'summary',
    ];

    protected function getFiltersMetadataClass(): string
    {
        return ApiAnalysisSubresourceFilters::class;
    }
}
