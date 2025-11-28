<?php

namespace App\Tests\Functional\Api\Resource;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Tests\Functional\Api\ApiTestProviderTrait;
use App\Tests\Functional\ApiTestRequestTrait;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class ApiResourceMicrostratigraphicUnitTest extends ApiTestCase
{
    use ApiTestRequestTrait;
    use ApiTestProviderTrait;

    private ?ParameterBagInterface $parameterBag = null;

    protected function setUp(): void
    {
        parent::setUp();
        static::$alwaysBootKernel = false;
        $this->parameterBag = self::getContainer()->get(ParameterBagInterface::class);
    }

    protected function tearDown(): void
    {
        $this->parameterBag = null;
        parent::tearDown();
    }

    public function testGetCollectionReturnsStratigraphicUnits(): void
    {
        $client = self::createClient();

        // Retrieve the first sample with microstratigraphy analysis
        $response = $this->apiRequest($client, 'GET', '/api/data/analyses/samples/microstratigraphy');
        $this->assertSame(200, $response->getStatusCode());
        $data = $response->toArray();
        $this->assertIsArray($data['member']);
        $this->assertNotEmpty($data['member']);
        $sampleId = $data['member'][0]['subject']['id'];

        // Retrieve the MU's linked to the sample
        $response = $this->apiRequest($client, 'GET', "/api/data/samples/$sampleId/microstratigraphic_units");
        $this->assertSame(200, $response->getStatusCode());
        $data = $response->toArray();
        $this->assertIsArray($data['member']);
        $this->assertNotEmpty($data['member']);
        $mus = $data['member'];

        foreach ($mus as $mu) {
            // Check that the MU's stratigraphic unit is linked to the sample
            $suId = basename($mu['stratigraphicUnit']['@id']);
            $response = $this->apiRequest($client, 'GET', "/api/data/stratigraphic_units/$suId/samples");
            $this->assertSame(200, $response->getStatusCode());
            $data = $response->toArray();
            $this->assertIsArray($data['member']);
            $this->assertNotEmpty($data['member']);
            // MU's stratigraphic unit should be linked to the sample
            $this->assertTrue(
                array_any(
                    $data['member'],
                    fn ($item) => $item['sample']['id'] === $sampleId)
            );
        }
    }
}
