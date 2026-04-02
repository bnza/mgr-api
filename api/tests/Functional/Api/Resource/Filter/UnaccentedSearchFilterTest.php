<?php

namespace App\Tests\Functional\Api\Resource\Filter;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Tests\Functional\ApiTestRequestTrait;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class UnaccentedSearchFilterTest extends ApiTestCase
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

    public function testFilterArchaeologicalSiteByName(): void
    {
        $client = self::createClient();

        // "Torre d'en Galmés" should match when searching "galmes" (accent è → e)
        $response = $this->apiRequest($client, 'GET', '/api/data/archaeological_sites?name=galmes');
        $this->assertSame(200, $response->getStatusCode());
        $data = $response->toArray();
        $this->assertGreaterThanOrEqual(1, count($data['member']));
        $this->assertStringContainsStringIgnoringCase('Galmés', $data['member'][0]['name']);
    }

    public function testFilterWrittenSourceByTitle(): void
    {
        $client = self::createClient();

        // "al-Talqīn fī l-fiqh al-mālikī" should match when searching "talqin"
        $response = $this->apiRequest($client, 'GET', '/api/data/history/written_sources?title=talqin');
        $this->assertSame(200, $response->getStatusCode());
        $data = $response->toArray();
        $this->assertGreaterThanOrEqual(1, count($data['member']));
        $this->assertStringContainsStringIgnoringCase('Talqīn', $data['member'][0]['title']);
    }

    public function testFilterWrittenSourceByAuthorValue(): void
    {
        $client = self::createClient();

        // Written sources by "Abū Ḥanīfa al-Dīnawarī" should match when searching "hanifa"
        $response = $this->apiRequest($client, 'GET', '/api/data/history/written_sources?author.value=hanifa');
        $this->assertSame(200, $response->getStatusCode());
        $data = $response->toArray();
        $this->assertGreaterThanOrEqual(1, count($data['member']));
    }

    public function testFilterWrittenSourceBySearchAliasMatchesTitle(): void
    {
        $client = self::createClient();

        // ?search=talqin should match "al-Talqīn fī l-fiqh al-mālikī" via title
        $response = $this->apiRequest($client, 'GET', '/api/data/history/written_sources?search=talqin');
        $this->assertSame(200, $response->getStatusCode());
        $data = $response->toArray();
        $this->assertGreaterThanOrEqual(1, count($data['member']));
        $this->assertStringContainsStringIgnoringCase('Talqīn', $data['member'][0]['title']);
    }

    public function testFilterWrittenSourceBySearchAliasMatchesAuthor(): void
    {
        $client = self::createClient();

        // ?search=hanifa should match written sources whose author.value contains "Ḥanīfa"
        $response = $this->apiRequest($client, 'GET', '/api/data/history/written_sources?search=hanifa');
        $this->assertSame(200, $response->getStatusCode());
        $data = $response->toArray();
        $this->assertGreaterThanOrEqual(1, count($data['member']));
    }

    public function testFilterWrittenSourceBySearchAliasNoMatch(): void
    {
        $client = self::createClient();

        // ?search=xyznonexistent should return empty collection
        $response = $this->apiRequest($client, 'GET', '/api/data/history/written_sources?search=xyznonexistent');
        $this->assertSame(200, $response->getStatusCode());
        $data = $response->toArray();
        $this->assertCount(0, $data['member']);
    }
}
