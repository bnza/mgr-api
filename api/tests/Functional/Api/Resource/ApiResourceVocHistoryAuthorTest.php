<?php

namespace App\Tests\Functional\Api\Resource;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Tests\Functional\ApiTestRequestTrait;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class ApiResourceVocHistoryAuthorTest extends ApiTestCase
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

    public function testFilterUnaccentedValueGetCollection(): void
    {
        $client = self::createClient();

        // "Abū Ḥanīfa al-Dīnawarī" should match when searching "hanifa"
        $response = $this->apiRequest($client, 'GET', '/api/vocabulary/history/authors?value=hanifa');
        $this->assertSame(200, $response->getStatusCode());
        $data = $response->toArray();
        $this->assertGreaterThanOrEqual(1, count($data['member']));
        $this->assertStringContainsStringIgnoringCase('anīfa', $data['member'][0]['value']);

        // Same search with transliterated chars should also match
        $response = $this->apiRequest($client, 'GET', '/api/vocabulary/history/authors?value=Ḥanīfa');
        $this->assertSame(200, $response->getStatusCode());
        $data = $response->toArray();
        $this->assertGreaterThanOrEqual(1, count($data['member']));
    }

    public function testFilterUnaccentedValueWithMacronGetCollection(): void
    {
        $client = self::createClient();

        // "Al-Bukhārī" should match when searching "bukhari"
        $response = $this->apiRequest($client, 'GET', '/api/vocabulary/history/authors?value=bukhari');
        $this->assertSame(200, $response->getStatusCode());
        $data = $response->toArray();
        $this->assertGreaterThanOrEqual(1, count($data['member']));
        $this->assertStringContainsStringIgnoringCase('Bukhārī', $data['member'][0]['value']);
    }

    public function testFilterUnaccentedValueWithUnderdotGetCollection(): void
    {
        $client = self::createClient();

        // "Al-Ṭighnarī" should match when searching "tighnar"
        $response = $this->apiRequest($client, 'GET', '/api/vocabulary/history/authors?value=tighnar');
        $this->assertSame(200, $response->getStatusCode());
        $data = $response->toArray();
        $this->assertGreaterThanOrEqual(1, count($data['member']));
        $this->assertStringContainsStringIgnoringCase('ighnarī', $data['member'][0]['value']);
    }

    public function testFilterUnaccentedVariantGetCollection(): void
    {
        $client = self::createClient();

        // variant "Al-Bāŷī" should match when searching "bayI" on variant field
        $response = $this->apiRequest($client, 'GET', '/api/vocabulary/history/authors?variant=bayi');
        $this->assertSame(200, $response->getStatusCode());
        $data = $response->toArray();
        $this->assertGreaterThanOrEqual(1, count($data['member']));
    }
}
