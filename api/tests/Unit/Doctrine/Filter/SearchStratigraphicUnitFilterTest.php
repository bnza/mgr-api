<?php

namespace App\Tests\Unit\Doctrine\Filter;

use App\Doctrine\Filter\SearchStratigraphicUnitFilter;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class SearchStratigraphicUnitFilterTest extends TestCase
{
    private SearchStratigraphicUnitFilter $filter;

    protected function setUp(): void
    {
        $managerRegistry = $this->createMock(ManagerRegistry::class);
        $logger = $this->createMock(LoggerInterface::class);

        $this->filter = new SearchStratigraphicUnitFilter($managerRegistry, $logger);
    }

    public function testSplitValueWithVariousDelimiters(): void
    {
        $reflection = new \ReflectionClass($this->filter);
        $method = $reflection->getMethod('splitValue');
        $method->setAccessible(true);

        // Test various non-word character delimiters
        $this->assertEquals(['ABC', '123'], $method->invoke($this->filter, 'ABC 123'));
        $this->assertEquals(['ABC', '123'], $method->invoke($this->filter, 'ABC.123'));
        $this->assertEquals(['ABC', '123'], $method->invoke($this->filter, 'ABC-123'));
        $this->assertEquals(['ABC', '123'], $method->invoke($this->filter, 'ABC_123'));
        $this->assertEquals(['ABC', '123'], $method->invoke($this->filter, 'ABC/123'));
        $this->assertEquals(['ABC', '123', '2023'], $method->invoke($this->filter, 'ABC.123.2023'));
    }

    public function testGetDescription(): void
    {
        $description = $this->filter->getDescription('App\Entity\Data\StratigraphicUnit');

        $this->assertArrayHasKey('search', $description);
        $this->assertEquals('search', $description['search']['property']);
        $this->assertEquals('string', $description['search']['type']);
        $this->assertFalse($description['search']['required']);
        $this->assertStringContainsString('Search stratigraphic units', $description['search']['description']);
    }
}
