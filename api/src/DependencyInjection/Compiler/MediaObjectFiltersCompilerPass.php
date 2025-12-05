<?php

namespace App\DependencyInjection\Compiler;

use App\Metadata\Attribute\SubResourceFilters\ApiMediaObjectSubresourceFilters;

final class MediaObjectFiltersCompilerPass extends AbstractSubresourceFiltersCompilerPass
{
    protected array $defaultBooleanProps = [
        'public',
    ];

    protected array $defaultExistsProps = [
        'description',
    ];

    protected array $defaultSearchProps = [
        'sha256' => 'exact',
        'originalFilename' => 'ipartial',
        'mimeType' => 'ipartial',
        'type.group' => 'exact',
        'type' => 'exact',
        'description' => 'ipartial',
        'uploadedBy.email' => 'ipartial',
        'uploadDate' => 'exact',
    ];

    protected array $defaultRangeProps = [
        'size',
        'uploadDate',
    ];

    protected array $defaultUnaccentedSearchProps = [
        'description',
    ];

    protected function getFiltersMetadataClass(): string
    {
        return ApiMediaObjectSubresourceFilters::class;
    }
}
