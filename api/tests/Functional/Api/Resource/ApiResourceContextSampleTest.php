<?php

namespace App\Tests\Functional\Api\Resource;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Tests\Functional\Api\ApiTestProviderTrait;
use App\Tests\Functional\ApiTestRequestTrait;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class ApiResourceContextSampleTest extends ApiTestCase
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

    public function testGetCollectionReturnsContextSamples(): void
    {
        $client = self::createClient();

        $response = $this->apiRequest($client, 'GET', '/api/data/context_samples');
        $this->assertSame(200, $response->getStatusCode());

        $data = $response->toArray();
        $this->assertIsArray($data['member']);
        // Should have fixture data available
        $this->assertNotEmpty($data['member']);

        $firstItem = $data['member'][0];
        $this->assertArrayHasKey('id', $firstItem);
        $this->assertArrayHasKey('context', $firstItem);
        $this->assertArrayHasKey('sample', $firstItem);
    }

    public function testPostCreatesContextSample(): void
    {
        $client = self::createClient();
        $token = $this->getUserToken($client, 'user_admin');

        // Use fixture data instead of creating new items
        $context = $this->getFixtureContextByName('floor 1');
        $sample = $this->getFixtureSampleBySiteAndNumber('ME', 95);

        $this->assertNotNull($context, 'Fixture context should exist');
        $this->assertNotNull($sample, 'Fixture sample should exist');

        $payload = [
            'context' => $context['@id'],
            'sample' => $sample['@id'],
        ];

        $response = $this->apiRequest($client, 'POST', '/api/data/context_samples', [
            'token' => $token,
            'json' => $payload,
        ]);

        $this->assertSame(201, $response->getStatusCode());
        $createdData = $response->toArray();
        $this->assertEquals($payload['context'], $createdData['context']);
        $this->assertEquals($payload['sample'], $createdData['sample']);
    }

    public function testPostValidationFailsWithMissingSample(): void
    {
        $client = self::createClient();
        $token = $this->getUserToken($client, 'user_admin');

        $contexts = $this->getFixtureContexts();

        $payload = [
            'context' => $contexts[0]['@id'],
        ];

        $response = $this->apiRequest($client, 'POST', '/api/data/context_samples', [
            'token' => $token,
            'json' => $payload,
        ]);

        $this->assertSame(422, $response->getStatusCode());
        $data = $response->toArray(false);
        $this->assertArrayHasKey('violations', $data);

        $sampleViolation = array_filter($data['violations'], fn ($violation) => 'sample' === $violation['propertyPath']);
        $this->assertNotEmpty($sampleViolation);
    }

    public function testPostValidationFailsWithMissingContext(): void
    {
        $client = self::createClient();
        $token = $this->getUserToken($client, 'user_admin');

        $sample = $this->getFixtureSampleBySiteAndNumber('ME', 95);

        $payload = [
            'sample' => $sample['@id'],
        ];

        $response = $this->apiRequest($client, 'POST', '/api/data/context_samples', [
            'token' => $token,
            'json' => $payload,
        ]);

        $this->assertSame(422, $response->getStatusCode());
        $data = $response->toArray(false);
        $this->assertArrayHasKey('violations', $data);

        $contextViolation = array_filter($data['violations'], fn ($violation) => 'context' === $violation['propertyPath']);
        $this->assertNotEmpty($contextViolation);
    }

    public function testPostValidationFailsIfRelatedEntitiesBelongToDifferentSites(): void
    {
        $client = self::createClient();
        $token = $this->getUserToken($client, 'user_admin');

        $sites = $this->getFixtureSites();

        $contexts = $this->getFixtureContexts();
        $samples = $this->getFixtureSamples();

        // Use fixture data from different sites
        $context = $contexts[0];
        $sample = $samples[0];

        $this->assertNotNull($context, 'Fixture context should exist');
        $this->assertNotNull($sample, 'Fixture sample should exist');

        $this->assertNotEquals($context['site']['@id'], $sample['site']['@id']);

        $payload = [
            'context' => $context['@id'],
            'sample' => $sample['@id'],
        ];

        $response = $this->apiRequest($client, 'POST', '/api/data/context_samples', [
            'token' => $token,
            'json' => $payload,
        ]);

        $this->assertSame(422, $response->getStatusCode());
        $data = $response->toArray(false);
        $this->assertArrayHasKey('violations', $data);

        $siteViolation = array_filter($data['violations'], fn ($violation) => str_contains(strtolower($violation['message']), 'same site'));
        $this->assertNotEmpty($siteViolation);
    }

    public function testPostUniqueConstraintViolation(): void
    {
        $client = self::createClient();
        $token = $this->getUserToken($client, 'user_admin');

        $contextSamples = $this->getFixtureContextSamples();

        $payload = [
            'context' => $contextSamples[0]['context'],
            'sample' => $contextSamples[0]['sample'],
        ];

        // Try to create the same relationship again - should fail with validation error
        $response = $this->apiRequest($client, 'POST', '/api/data/context_samples', [
            'token' => $token,
            'json' => $payload,
        ]);

        // Should return 422 validation error, not 500 database error
        $this->assertSame(422, $response->getStatusCode());
        $data = $response->toArray(false);
        $this->assertArrayHasKey('violations', $data);
        $this->assertGreaterThan(0, count($data['violations']));

        // Check that the violation is about uniqueness
        $violations = $data['violations'];
        $uniqueViolation = array_filter($violations, function ($violation) {
            return str_contains(strtolower($violation['message']), 'duplicate');
        });
        $this->assertNotEmpty($uniqueViolation, 'Should have a uniqueness violation');
    }

    /**
     * Helper method to get fixture context samples.
     */
    protected function getFixtureContextSamples(array $queryParams = []): array
    {
        $client = self::createClient();
        $url = '/api/data/context_samples';
        if (!empty($queryParams)) {
            $url .= '?'.http_build_query($queryParams);
        }
        $response = $this->apiRequest($client, 'GET', $url);
        $this->assertSame(200, $response->getStatusCode());

        return $response->toArray()['member'];
    }

    /**
     * Helper method to get fixture samples.
     */
    protected function getFixtureSamples(array $queryParams = []): array
    {
        $client = self::createClient();
        $url = '/api/data/samples';
        if (!empty($queryParams)) {
            $url .= '?'.http_build_query($queryParams);
        }
        $response = $this->apiRequest($client, 'GET', $url);
        $this->assertSame(200, $response->getStatusCode());

        return $response->toArray()['member'];
    }

    /**
     * Helper method to get a specific sample by site and number.
     */
    protected function getFixtureSampleBySiteAndNumber(string $siteCode, int $number): ?array
    {
        $samples = $this->getFixtureSamples();

        foreach ($samples as $sample) {
            if (isset($sample['site']['code']) && $sample['site']['code'] === $siteCode && $sample['number'] === $number) {
                return $sample;
            }
        }

        return null;
    }
}
