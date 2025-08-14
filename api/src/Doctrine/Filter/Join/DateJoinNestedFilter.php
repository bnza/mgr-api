<?php

namespace App\Doctrine\Filter\Join;

use ApiPlatform\Doctrine\Orm\Filter\DateFilter;
use ApiPlatform\Doctrine\Orm\Filter\FilterInterface;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;

class DateJoinNestedFilter extends AbstractJoinNestedFilter
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
        return preg_replace('/\[(before|strictly_before|after|strictly_after)]$/', '', $property);
    }

    protected function generateDescriptionEntries(string $property, string $targetProperty): array
    {
        $filterProperty = sprintf('%s.%s', $property, $targetProperty);
        $description = [];

        // Add basic property without operator
        $basicEntry = $this->createBaseDescriptionEntry($filterProperty);
        $basicEntry['description'] = sprintf(
            'Filter by %s.%s using many-to-many relationship (exact date match)',
            $property,
            $targetProperty
        );
        $basicEntry['openapi'] = [
            'example' => '2024-01-01',
            'allowReserved' => false,
            'explode' => false,
        ];
        $description[$filterProperty] = $basicEntry;

        // Add operators
        $operators = [
            'before' => 'before or equal to the specified date',
            'strictly_before' => 'strictly before the specified date',
            'after' => 'after or equal to the specified date',
            'strictly_after' => 'strictly after the specified date',
        ];

        $openApiExample = [
            'example' => '2024-01-01',
            'allowReserved' => false,
            'explode' => false,
        ];

        return array_merge(
            $description,
            $this->createOperatorDescriptions($filterProperty, $property, $targetProperty, $operators, $openApiExample)
        );
    }

    protected function createTargetFilter(string $targetProperty, $strategy): FilterInterface
    {
        return new DateFilter(
            $this->managerRegistry,
            $this->logger,
            [$targetProperty => $strategy],
            $this->nameConverter,
        );
    }
}
