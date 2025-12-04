<?php

namespace App\Metadata\Resource\Factory;

use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use App\Metadata\Attribute\AbstractSubresourceFilters;
use App\Metadata\Attribute\SubResourceFilterType;
use Symfony\Component\DependencyInjection\Attribute\AutowireDecorated;

abstract readonly class AbstractSubResourceFiltersMetadataCollectionFactory implements ResourceMetadataCollectionFactoryInterface
{
    /**
     * @return class-string<AbstractSubresourceFilters>
     */
    abstract protected function getFiltersClass(): string;

    public function __construct(
        #[AutowireDecorated]
        private ResourceMetadataCollectionFactoryInterface $decorated,
    ) {
    }

    public function create(string $resourceClass): ResourceMetadataCollection
    {
        $collection = $this->decorated->create($resourceClass);

        try {
            $refl = new \ReflectionClass($resourceClass);
        } catch (\ReflectionException) {
            return $collection;
        }

        $filtersClass = $this->getFiltersClass();

        $attrs = $refl->getAttributes($filtersClass);
        if (!$attrs) {
            return $collection;
        }

        foreach ($collection as $i => $resource) {
            $ops = $resource->getOperations() ?? null;
            if ($ops) {
                foreach ($ops as $name => $op) {
                    if ($op instanceof GetCollection) {
                        $filters = $op->getFilters() ?? [];
                        foreach ($attrs as $attr) {
                            $suffix = $attr->newInstance()->getIdSuffix();
                            foreach (SubResourceFilterType::cases() as $type) {
                                $filters[] = $filtersClass::getDefinitionId($resourceClass, $type, $suffix);
                            }
                        }
                        $ops->add($name, $op->withFilters(array_values(array_unique($filters))));
                    }
                }
                $collection[$i] = $resource->withOperations($ops);
            }
        }

        return $collection;
    }
}
