<?php

namespace App\Metadata\Resource\Factory;

use App\Metadata\Attribute\ApiStratigraphicUnitSubresourceFilters;
use Symfony\Component\DependencyInjection\Attribute\AsDecorator;

#[AsDecorator(
    'api_platform.metadata.resource.metadata_collection_factory',
    priority: 1200 // ensure it runs before ParameterResourceMetadataCollectionFactory (1000)
)]
final readonly class StratigraphicUnitSubResourceFiltersMetadataCollectionFactory extends AbstractSubResourceFiltersMetadataCollectionFactory
{
    protected function getFiltersClass(): string
    {
        return ApiStratigraphicUnitSubresourceFilters::class;
    }
}
