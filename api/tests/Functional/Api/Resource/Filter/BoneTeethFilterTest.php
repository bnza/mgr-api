<?php

namespace App\Tests\Functional\Api\Resource\Filter;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Symfony\Bundle\Test\Client;
use App\Tests\Functional\Api\ApiTestProviderTrait;
use App\Tests\Functional\ApiTestRequestTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class BoneTeethFilterTest extends ApiTestCase
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
    public static function truthyValuesProvider(): array
    {
        return [
            'boolean true' => ['value' => true],
            'string "1"' => ['value' => '1'],
            'string "true"' => ['value' => 'true'],
            'string "TRUE"' => ['value' => 'TRUE'],
            'string "yes"' => ['value' => 'yes'],
            'string "YES"' => ['value' => 'YES'],
            'string "on"' => ['value' => 'on'],
            'string "ON"' => ['value' => 'ON'],
            'integer 1' => ['value' => 1],
            'integer 2' => ['value' => 2],
            'integer -1' => ['value' => -1],
            'float 1.5' => ['value' => 1.5],
            'string "2"' => ['value' => '2'],
            'string "any"' => ['value' => 'any'],
            'string with spaces "  true  "' => ['value' => '  true  '],
        ];
    }

    #[DataProvider('truthyValuesProvider')]
    public function testTeethFilterWithTruthyValueShouldReturnOnlyMaxAndNCodes(mixed $value): void
    {
        $client = self::createClient();

        // Test with various truthy values
        $response = $this->apiRequest($client, 'GET', '/api/vocabulary/zoo/bones', [
            'query' => ['teeth' => $value],
        ]);

        $this->assertSame(200, $response->getStatusCode());
        $responseData = $response->toArray();
        $filteredBones = $responseData['member'];

        // Should return only bones with code 'MAX' or 'N'
        $this->assertSame(2, $responseData['totalItems']);

        // Verify all returned bones have code 'MAX' or 'N'
        foreach ($filteredBones as $bone) {
            $this->assertContains(
                $bone['code'],
                ['MAX', 'N'],
                "Bone with code '{$bone['code']}' should not be returned when teeth filter is active with value: ".var_export($value, true)
            );
        }
    }
}
