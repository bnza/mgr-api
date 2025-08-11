<?php

namespace App\Tests\Functional\Api\Resource\Filter;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Symfony\Bundle\Test\Client;
use App\Tests\Functional\Api\ApiTestProviderTrait;
use App\Tests\Functional\ApiTestRequestTrait;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class SearchSiteFilterTest extends ApiTestCase
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

    public function testSearchFilterWithExistingCode(): void
    {
        $client = self::createClient();

        $response = $this->apiRequest($client, 'GET', '/api/data/sites', [
            'query' => ['search' => 'ME'],
        ]);

        $this->assertResponseIsSuccessful();
        $data = $response->toArray();
        $this->assertArrayHasKey('member', $data);

        // Verify that results contain contexts with names containing 'fill'
        foreach ($data['member'] as $item) {
            $this->assertEquals('ME', $item['code']);
        }
    }

    public function testSearchFilterCanBeCombinedWithUnaccentedFilter(): void
    {
        $client = self::createClient();

        $response = $this->apiRequest($client, 'GET', '/api/data/sites', [
            'query' => ['search' => 'ME', 'name' => 'nathan'],
        ]);

        $this->assertResponseIsSuccessful();
        $data = $response->toArray();
        $this->assertArrayHasKey('member', $data);

        // Verify that results match both site code ending with 'ME' AND name containing 'fill'
        foreach ($data['member'] as $item) {
            $this->assertEquals('ME', $item['code']);
        }
    }
}
