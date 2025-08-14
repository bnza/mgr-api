<?php

namespace App\Doctrine\Filter\Join;

use ApiPlatform\Doctrine\Orm\Filter\FilterInterface;
use ApiPlatform\Doctrine\Orm\Filter\RangeFilter;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;

class RangeJoinNestedFilter extends AbstractJoinNestedFilter
{
    public function __construct(
        ManagerRegistry $managerRegistry,
        ?LoggerInterface $logger = null,
        ?array $properties = null,
        ?NameConverterInterface $nameConverter = null,
    ) {
        parent::__construct($managerRegistry, $logger, $properties, $nameConverter);
    }

    protected function extractBaseProperty(string $property): string
    {
        return preg_replace('/\[(gt|gte|lt|lte|between)]$/', '', $property);
    }

    protected function generateDescriptionEntries(string $property, string $targetProperty): array
    {
        $filterProperty = sprintf('%s.%s', $property, $targetProperty);

        $operators = [
            'gt' => 'greater than',
            'gte' => 'greater than or equal',
            'lt' => 'less than',
            'lte' => 'less than or equal',
            'between' => 'between',
        ];

        return $this->createOperatorDescriptions($filterProperty, $property, $targetProperty, $operators);
    }

    protected function createTargetFilter(string $targetProperty, $strategy): FilterInterface
    {
        return new RangeFilter(
            $this->managerRegistry,
            $this->logger,
            [$targetProperty => null],
            $this->nameConverter,
        );
    }
}
