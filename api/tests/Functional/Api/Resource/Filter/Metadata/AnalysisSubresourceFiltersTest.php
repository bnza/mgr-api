<?php

namespace App\Tests\Functional\Api\Resource\Filter\Metadata;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Symfony\Bundle\Test\Client;
use App\Tests\Functional\Api\ApiTestProviderTrait;
use App\Tests\Functional\ApiTestRequestTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class AnalysisSubresourceFiltersTest extends ApiTestCase
{
    use ApiTestRequestTrait;
    use ApiTestProviderTrait;

    private Client $client;
    private ?ParameterBagInterface $parameterBag = null;

    protected function setUp(): void
    {
        parent::setUp();
        static::$alwaysBootKernel = false;
        $this->parameterBag = self::getContainer()->get(ParameterBagInterface::class);
        $this->client = static::createClient();
    }

    protected function tearDown(): void
    {
        $this->parameterBag = null;
        parent::tearDown();
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function pathVariablesProvider(): \Generator
    {
        $datasets = [
            '/api/data/analyses/contexts/botany' => [
                'analysis.createdBy.email[]',
                'analysis.identifier',
                'analysis.laboratory',
                'analysis.responsible',
                'analysis.status[]',
                'analysis.summary',
                'analysis.type[]',
                'analysis.year[]',
                'analysis.year[between]',
                'analysis.year[gt]',
                'analysis.year[gte]',
                'analysis.year[lt]',
                'analysis.year[lte]',
                'exists[analysis.laboratory]',
                'exists[analysis.responsible]',
                'exists[analysis.summary]',
            ],
        ];

        foreach ($datasets as $path => $variables) {
            // Note: $path is used as dataset name AND passed as first argument
            yield $path => [$path, $variables];
        }
    }

    #[DataProvider('pathVariablesProvider')]
    public function testAnalysisContextBotanyQueryParams(string $path, array $variables): void
    {
        $client = self::createClient();

        // Test with various truthy values
        $response = $this->apiRequest($client, 'GET', $path);

        $this->assertSame(200, $response->getStatusCode());

        $mappings = $response->toArray()['search']['mapping'];
        foreach ($variables as $variable) {
            $this->assertTrue(array_any($mappings, fn ($mapping) => $mapping['variable'] === $variable), sprintf('Mapping for variable "%s" not found.', $variable));
        }
    }
}
