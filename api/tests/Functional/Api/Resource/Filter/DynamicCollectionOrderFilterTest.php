<?php

namespace App\Tests\Functional\Api\Resource\Filter;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Tests\Functional\ApiTestRequestTrait;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class DynamicCollectionOrderFilterTest extends ApiTestCase
{
    use ApiTestRequestTrait;

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

    public function testAscSortingByChronologyLower(): void
    {
        $client = self::createClient();
        $token = $this->getUserToken($client, 'user_editor');

        $response = $this->apiRequest(
            $client,
            'GET',
            '/api/data/history/written_sources?order[centuries.century.chronologyLower]=asc',
            ['token' => $token]
        );

        $this->assertResponseIsSuccessful();
        $data = $response->toArray();
        $items = $data['member'];

        $lastMinLower = -999999;
        foreach ($items as $item) {
            $minLower = 999999;
            if (empty($item['centuries'])) {
                continue;
            }
            foreach ($item['centuries'] as $centuryJoin) {
                $lower = $centuryJoin['chronologyLower'];
                if ($lower < $minLower) {
                    $minLower = $lower;
                }
            }
            $this->assertGreaterThanOrEqual($lastMinLower, $minLower, 'ASC Sort failed for ID '.$item['id']);
            $lastMinLower = $minLower;
        }
    }

    public function testDescSortingByChronologyUpper(): void
    {
        $client = self::createClient();
        $token = $this->getUserToken($client, 'user_editor');

        $response = $this->apiRequest(
            $client,
            'GET',
            '/api/data/history/written_sources?order[centuries.century.chronologyUpper]=desc',
            ['token' => $token]
        );

        $this->assertResponseIsSuccessful();
        $data = $response->toArray();
        $items = $data['member'];

        $lastMaxUpper = 999999;
        foreach ($items as $item) {
            $maxUpper = -999999;
            if (empty($item['centuries'])) {
                continue;
            }
            foreach ($item['centuries'] as $centuryJoin) {
                $upper = $centuryJoin['chronologyUpper'];
                if ($upper > $maxUpper) {
                    $maxUpper = $upper;
                }
            }
            $this->assertLessThanOrEqual($lastMaxUpper, $maxUpper, 'DESC Sort failed for ID '.$item['id']);
            $lastMaxUpper = $maxUpper;
        }
    }
}
