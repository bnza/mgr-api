<?php

namespace App\Metadata\Resource\Factory;

use App\Metadata\Attribute\SubResourceFilters\ApiMediaObjectSubresourceFilters;
use Symfony\Component\DependencyInjection\Attribute\AsDecorator;

#[AsDecorator(
    'api_platform.metadata.resource.metadata_collection_factory',
    priority: 1200 // ensure it runs before ParameterResourceMetadataCollectionFactory (1000)
)]
final readonly class MediaObjectSubResourceFiltersMetadataCollectionFactory extends AbstractSubResourceFiltersMetadataCollectionFactory
{
    protected function getFiltersClass(): string
    {
        return ApiMediaObjectSubresourceFilters::class;
    }
}
