<?php

namespace App\Doctrine\Filter\Join;

use ApiPlatform\Doctrine\Orm\Filter\FilterInterface;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\IriConverterInterface;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;

class SearchJoinNestedFilter extends AbstractJoinNestedFilter
{
    public function __construct(
        private IriConverterInterface $iriConverter,
        ManagerRegistry $managerRegistry,
        ?LoggerInterface $logger = null,
        ?array $properties = null,
        ?NameConverterInterface $nameConverter = null,
    ) {
        parent::__construct($managerRegistry, $logger, $properties, $nameConverter);
    }

    protected function createTargetFilter(string $targetProperty, $strategy): FilterInterface
    {
        return new SearchFilter(
            $this->managerRegistry,
            $this->iriConverter,
            null,
            $this->logger,
            [$targetProperty => $strategy],
            null,
            $this->nameConverter,
        );
    }
}
