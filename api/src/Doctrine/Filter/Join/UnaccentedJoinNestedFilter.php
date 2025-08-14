<?php

namespace App\Doctrine\Filter\Join;

use ApiPlatform\Doctrine\Orm\Filter\FilterInterface;
use App\Doctrine\Filter\UnaccentedSearchFilter;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;
use Symfony\Component\PropertyInfo\Type;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;

class UnaccentedJoinNestedFilter extends AbstractJoinNestedFilter
{
    public function __construct(
        ManagerRegistry $managerRegistry,
        ?LoggerInterface $logger = null,
        ?array $properties = null,
        ?NameConverterInterface $nameConverter = null,
    ) {
        parent::__construct($managerRegistry, $logger, $properties, $nameConverter);
    }

    protected function generateDescriptionEntries(string $property, string $targetProperty): array
    {
        $filterProperty = sprintf('%s.%s', $property, $targetProperty);

        return [
            $filterProperty => [
                'property' => $filterProperty,
                'type' => Type::BUILTIN_TYPE_STRING,
                'required' => false,
                'description' => sprintf(
                    'Filter by %s.%s using many-to-many relationship with case insensitive unaccented string matching',
                    $property,
                    $targetProperty
                ),
                'openapi' => [
                    'example' => 'cafè',
                    'allowReserved' => false,
                    'allowEmptyValue' => true,
                    'explode' => false,
                ],
            ],
        ];
    }

    protected function createTargetFilter(string $targetProperty, $strategy): FilterInterface
    {
        return new UnaccentedSearchFilter(
            $this->managerRegistry,
            $this->logger,
            [$targetProperty => $strategy],
            $this->nameConverter,
        );
    }
}
