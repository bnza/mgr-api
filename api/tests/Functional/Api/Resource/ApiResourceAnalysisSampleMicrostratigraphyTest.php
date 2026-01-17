<?php

namespace App\Tests\Functional\Api\Resource;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Tests\Functional\Api\ApiTestProviderTrait;
use App\Tests\Functional\ApiTestRequestTrait;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class ApiResourceAnalysisSampleMicrostratigraphyTest extends ApiTestCase
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

    public function testPostGetCollectionWholeAclReturnsFalseForUnauthenticatedUser(): void
    {
        $client = self::createClient();

        $analysesJoinResponse = $this->apiRequest($client, 'GET', '/api/data/analyses/samples/microstratigraphy');
        $analysisJoin = $this->getRandomMemberItem($analysesJoinResponse->toArray());
        $sampleId = basename($analysisJoin['subject']['@id']);
        $stratigraphicUnitsJoinResponse = $this->apiRequest($client, 'GET', "/api/data/samples/$sampleId/stratigraphic_units");
        $stratigraphicUnitsJoin = $this->getRandomMemberItem($stratigraphicUnitsJoinResponse->toArray());
        $suId = basename($stratigraphicUnitsJoin['stratigraphicUnit']['@id']);

        $collectionResponse = $this->apiRequest($client, 'GET', "/api/data/stratigraphic_units/$suId/analyses/samples/microstratigraphy");
        $collection = $collectionResponse->toArray();
        $this->arrayHasKey('member', $collection);
        $this->assertNotEmpty($collection['member']);

        foreach ($collection['member'] as $item) {
            $this->assertArrayHasKey('subject', $item);
            $this->assertEquals('Sample', $item['subject']['@type']);
            $sampleId = basename($analysisJoin['subject']['@id']);
            $stratigraphicUnitsJoinResponse = $this->apiRequest($client, 'GET', "/api/data/samples/$sampleId/stratigraphic_units");
            $joinStratigraphicUnits = $stratigraphicUnitsJoinResponse->toArray();
            $this->assertArrayHasKey('member', $joinStratigraphicUnits);
            $this->assertNotEmpty($joinStratigraphicUnits['member']);
            $suIds = array_map(fn ($su) => basename($su['stratigraphicUnit']['@id']), $joinStratigraphicUnits['member']);
            $this->assertContains($suId, $suIds);
        }
    }
}
