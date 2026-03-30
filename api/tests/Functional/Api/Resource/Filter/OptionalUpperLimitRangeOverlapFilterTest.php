<?php

namespace App\Tests\Functional\Api\Resource\Filter;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Tests\Functional\Api\ApiTestProviderTrait;
use App\Tests\Functional\ApiTestRequestTrait;

class OptionalUpperLimitRangeOverlapFilterTest extends ApiTestCase
{
    use ApiTestRequestTrait;
    use ApiTestProviderTrait;

    private const string ENDPOINT = '/api/data/history/written_sources_cited_works';

    private ?\Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface $parameterBag = null;

    protected function setUp(): void
    {
        parent::setUp();
        static::$alwaysBootKernel = false;
        $this->parameterBag = self::getContainer()->get(\Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface::class);
    }

    protected function tearDown(): void
    {
        $this->parameterBag = null;
        parent::tearDown();
    }

    /**
     * Test exact match: ?yearCompleted=2025
     * Should return records where 2025 is within [yearCompleted, yearCompletedUpper].
     */
    public function testExactMatch(): void
    {
        $target = 2025;
        $client = self::createClient();
        $token = $this->getUserToken($client, 'user_his');

        $response = $this->apiRequest($client, 'GET', self::ENDPOINT, [
            'token' => $token,
            'query' => ['yearCompleted' => $target],
        ]);

        $this->assertResponseIsSuccessful();
        $data = $response->toArray();
        $this->assertNotEmpty($data['member'], 'The collection should not be empty for a valid year.');

        foreach ($data['member'] as $member) {
            $lower = $member['yearCompleted'];
            $upper = $member['yearCompletedUpper'] ?? $lower;
            $this->assertLessThanOrEqual($target, $lower, "Lower bound ($lower) should be <= target ($target)");
            $this->assertGreaterThanOrEqual($target, $upper, "Upper bound ($upper) should be >= target ($target)");
        }
    }

    /**
     * Test GT operator: ?yearCompleted[gt]=2025
     * Should return records where yearCompletedUpper > 2025.
     */
    public function testGtOperator(): void
    {
        $target = 2025;
        $client = self::createClient();
        $token = $this->getUserToken($client, 'user_his');

        $response = $this->apiRequest($client, 'GET', self::ENDPOINT, [
            'token' => $token,
            'query' => ['yearCompleted' => ['gt' => $target]],
        ]);

        $this->assertResponseIsSuccessful();
        $data = $response->toArray();
        $this->assertNotEmpty($data['member'], 'The collection should not be empty.');

        foreach ($data['member'] as $member) {
            $lower = $member['yearCompleted'];
            $upper = $member['yearCompletedUpper'] ?? $lower;
            $this->assertGreaterThan($target, $upper, "Upper bound ($upper) should be > target ($target)");
        }
    }

    /**
     * Test GTE operator: ?yearCompleted[gte]=2025
     * Should return records where yearCompletedUpper >= 2025.
     */
    public function testGteOperator(): void
    {
        $target = 2025;
        $client = self::createClient();
        $token = $this->getUserToken($client, 'user_his');

        $response = $this->apiRequest($client, 'GET', self::ENDPOINT, [
            'token' => $token,
            'query' => ['yearCompleted' => ['gte' => $target]],
        ]);

        $this->assertResponseIsSuccessful();
        $data = $response->toArray();
        $this->assertNotEmpty($data['member'], 'The collection should not be empty.');

        foreach ($data['member'] as $member) {
            $lower = $member['yearCompleted'];
            $upper = $member['yearCompletedUpper'] ?? $lower;
            $this->assertGreaterThanOrEqual($target, $upper, "Upper bound ($upper) should be >= target ($target)");
        }
    }

    /**
     * Test LT operator: ?yearCompleted[lt]=2025
     * Should return records where yearCompleted < 2025.
     */
    public function testLtOperator(): void
    {
        $target = 2025;
        $client = self::createClient();
        $token = $this->getUserToken($client, 'user_his');

        $response = $this->apiRequest($client, 'GET', self::ENDPOINT, [
            'token' => $token,
            'query' => ['yearCompleted' => ['lt' => $target]],
        ]);

        $this->assertResponseIsSuccessful();
        $data = $response->toArray();
        $this->assertNotEmpty($data['member'], 'The collection should not be empty.');

        foreach ($data['member'] as $member) {
            $lower = $member['yearCompleted'];
            $this->assertLessThan($target, $lower, "Lower bound ($lower) should be < target ($target)");
        }
    }

    /**
     * Test LTE operator: ?yearCompleted[lte]=2025
     * Should return records where yearCompleted <= 2025.
     */
    public function testLteOperator(): void
    {
        $target = 2025;
        $client = self::createClient();
        $token = $this->getUserToken($client, 'user_his');

        $response = $this->apiRequest($client, 'GET', self::ENDPOINT, [
            'token' => $token,
            'query' => ['yearCompleted' => ['lte' => $target]],
        ]);

        $this->assertResponseIsSuccessful();
        $data = $response->toArray();
        $this->assertNotEmpty($data['member'], 'The collection should not be empty.');

        foreach ($data['member'] as $member) {
            $lower = $member['yearCompleted'];
            $this->assertLessThanOrEqual($target, $lower, "Lower bound ($lower) should be <= target ($target)");
        }
    }

    /**
     * Test BETWEEN operator: ?yearCompleted[between]=2024..2024
     * Record overlaps if (lower <= target_end) AND (upper >= target_start).
     */
    public function testBetweenOperator(): void
    {
        $targetStart = 2024;
        $targetEnd = 2024;
        $client = self::createClient();
        $token = $this->getUserToken($client, 'user_his');

        $response = $this->apiRequest($client, 'GET', self::ENDPOINT, [
            'token' => $token,
            'query' => ['yearCompleted' => ['between' => "$targetStart..$targetEnd"]],
        ]);

        $this->assertResponseIsSuccessful();
        $data = $response->toArray();
        $this->assertNotEmpty($data['member'], 'The collection should not be empty.');

        foreach ($data['member'] as $member) {
            $lower = $member['yearCompleted'];
            $upper = $member['yearCompletedUpper'] ?? $lower;
            $this->assertLessThanOrEqual($targetEnd, $lower, "Lower bound ($lower) should be <= targetEnd ($targetEnd)");
            $this->assertGreaterThanOrEqual($targetStart, $upper, "Upper bound ($upper) should be >= targetStart ($targetStart)");
        }
    }
}
