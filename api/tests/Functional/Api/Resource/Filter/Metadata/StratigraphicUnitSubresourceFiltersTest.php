<?php

namespace App\Tests\Functional\Api\Resource\Filter\Metadata;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Symfony\Bundle\Test\Client;
use App\Tests\Functional\Api\ApiTestProviderTrait;
use App\Tests\Functional\ApiTestRequestTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class StratigraphicUnitSubresourceFiltersTest extends ApiTestCase
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
            '/api/data/analyses/botany/charcoals' => [
                'subject.stratigraphicUnit.area[]',
                'subject.stratigraphicUnit.building[]',
                'subject.stratigraphicUnit.chronologyLower[]',
                'subject.stratigraphicUnit.chronologyLower[between]',
                'subject.stratigraphicUnit.chronologyLower[gt]',
                'subject.stratigraphicUnit.chronologyLower[gte]',
                'subject.stratigraphicUnit.chronologyLower[lt]',
                'subject.stratigraphicUnit.chronologyLower[lte]',
                'subject.stratigraphicUnit.chronologyUpper[]',
                'subject.stratigraphicUnit.chronologyUpper[between]',
                'subject.stratigraphicUnit.chronologyUpper[gt]',
                'subject.stratigraphicUnit.chronologyUpper[gte]',
                'subject.stratigraphicUnit.chronologyUpper[lt]',
                'subject.stratigraphicUnit.chronologyUpper[lte]',
                'subject.stratigraphicUnit.description',
                'subject.stratigraphicUnit.interpretation',
                'subject.stratigraphicUnit.number[]',
                'subject.stratigraphicUnit.number[between]',
                'subject.stratigraphicUnit.number[gt]',
                'subject.stratigraphicUnit.number[gte]',
                'subject.stratigraphicUnit.number[lt]',
                'subject.stratigraphicUnit.number[lte]',
                'subject.stratigraphicUnit.site[]',
                'subject.stratigraphicUnit.year[]',
                'subject.stratigraphicUnit.year[between]',
                'subject.stratigraphicUnit.year[gt]',
                'subject.stratigraphicUnit.year[gte]',
                'subject.stratigraphicUnit.year[lt]',
                'subject.stratigraphicUnit.year[lte]',
            ],
            '/api/data/analyses/contexts/botany' => [
                'subject.contextStratigraphicUnits.stratigraphicUnit.area[]',
                'subject.contextStratigraphicUnits.stratigraphicUnit.building[]',
                'subject.contextStratigraphicUnits.stratigraphicUnit.chronologyLower[]',
                'subject.contextStratigraphicUnits.stratigraphicUnit.chronologyLower[between]',
                'subject.contextStratigraphicUnits.stratigraphicUnit.chronologyLower[gt]',
                'subject.contextStratigraphicUnits.stratigraphicUnit.chronologyLower[gte]',
                'subject.contextStratigraphicUnits.stratigraphicUnit.chronologyLower[lt]',
                'subject.contextStratigraphicUnits.stratigraphicUnit.chronologyLower[lte]',
                'subject.contextStratigraphicUnits.stratigraphicUnit.chronologyUpper[]',
                'subject.contextStratigraphicUnits.stratigraphicUnit.chronologyUpper[between]',
                'subject.contextStratigraphicUnits.stratigraphicUnit.chronologyUpper[gt]',
                'subject.contextStratigraphicUnits.stratigraphicUnit.chronologyUpper[gte]',
                'subject.contextStratigraphicUnits.stratigraphicUnit.chronologyUpper[lt]',
                'subject.contextStratigraphicUnits.stratigraphicUnit.chronologyUpper[lte]',
                'subject.contextStratigraphicUnits.stratigraphicUnit.description',
                'subject.contextStratigraphicUnits.stratigraphicUnit.interpretation',
                'subject.contextStratigraphicUnits.stratigraphicUnit.number[]',
                'subject.contextStratigraphicUnits.stratigraphicUnit.number[between]',
                'subject.contextStratigraphicUnits.stratigraphicUnit.number[gt]',
                'subject.contextStratigraphicUnits.stratigraphicUnit.number[gte]',
                'subject.contextStratigraphicUnits.stratigraphicUnit.number[lt]',
                'subject.contextStratigraphicUnits.stratigraphicUnit.number[lte]',
                'subject.contextStratigraphicUnits.stratigraphicUnit.site[]',
                'subject.contextStratigraphicUnits.stratigraphicUnit.year[]',
                'subject.contextStratigraphicUnits.stratigraphicUnit.year[between]',
                'subject.contextStratigraphicUnits.stratigraphicUnit.year[gt]',
                'subject.contextStratigraphicUnits.stratigraphicUnit.year[gte]',
                'subject.contextStratigraphicUnits.stratigraphicUnit.year[lt]',
                'subject.contextStratigraphicUnits.stratigraphicUnit.year[lte]',
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
