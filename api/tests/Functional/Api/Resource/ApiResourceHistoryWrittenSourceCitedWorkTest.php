<?php

namespace App\Tests\Functional\Api\Resource;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Tests\Functional\Api\ApiTestProviderTrait;
use App\Tests\Functional\ApiTestRequestTrait;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class ApiResourceHistoryWrittenSourceCitedWorkTest extends ApiTestCase
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

        $collectionResponse = $this->apiRequest($client, 'GET', '/api/data/history/written_sources_cited_works');
        $collection = $collectionResponse->toArray();
        $this->assertArrayHasKey('_acl', $collection);
        $this->assertFalse($collection['_acl']['canCreate']);
    }

    public function testPostGetCollectionWholeAclReturnsTrueForHistorianUser(): void
    {
        $client = self::createClient();
        $token = $this->getUserToken($client, 'user_his');

        $collectionResponse = $this->apiRequest($client, 'GET', '/api/data/history/written_sources_cited_works', ['token' => $token]);
        $collection = $collectionResponse->toArray();
        $this->assertArrayHasKey('_acl', $collection);
        $this->assertTrue($collection['_acl']['canCreate']);
    }

    public function testPostWrittenSourceCitedWorkIsAllowedForHistorianUser(): void
    {
        $client = self::createClient();
        $token = $this->getUserToken($client, 'user_his');

        $writtenSourceIri = $this->createWrittenSource($token);
        $citedWork = $this->getVocabulary('history/cited_works')[0];

        $payload = [
            'writtenSource' => $writtenSourceIri,
            'citedWork' => $citedWork['@id'],
            'yearCompleted' => 2024,
        ];

        $response = $this->apiRequest($client, 'POST', '/api/data/history/written_sources_cited_works', [
            'token' => $token,
            'json' => $payload,
        ]);

        $this->assertSame(201, $response->getStatusCode(), (string) $response->getContent(false));
        $responseData = $response->toArray();
        $this->assertSame($payload['yearCompleted'], $responseData['yearCompleted']);
    }

    public function testPatchWrittenSourceCitedWorkIsAllowedForHistorianUser(): void
    {
        $client = self::createClient();
        $token = $this->getUserToken($client, 'user_his');

        $citedWorkRelIri = $this->createWrittenSourceCitedWork($token);
        $id = $this->getIdFromIri($citedWorkRelIri);

        $payload = [
            'yearCompleted' => 2025,
        ];

        $response = $this->apiRequest($client, 'PATCH', "/api/data/history/written_sources_cited_works/$id", [
            'token' => $token,
            'json' => $payload,
        ]);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(2025, $response->toArray()['yearCompleted']);
    }

    public function testDeleteWrittenSourceCitedWorkIsAllowedForHistorianUser(): void
    {
        $client = self::createClient();
        $token = $this->getUserToken($client, 'user_his');

        $citedWorkRelIri = $this->createWrittenSourceCitedWork($token);
        $id = $this->getIdFromIri($citedWorkRelIri);

        $response = $this->apiRequest($client, 'DELETE', "/api/data/history/written_sources_cited_works/$id", [
            'token' => $token,
        ]);

        $this->assertSame(204, $response->getStatusCode());
    }

    private function createWrittenSource(string $token): string
    {
        $client = self::createClient();
        $author = $this->getVocabulary('history/authors')[0];
        $type = $this->getVocabulary('history/written_source_types')[0];

        $payload = [
            'author' => $author['@id'],
            'writtenSourceType' => $type['@id'],
            'title' => 'Test Source for Relationship',
            'publicationDetails' => 'Test Details',
        ];

        $response = $this->apiRequest($client, 'POST', '/api/data/history/written_sources', [
            'token' => $token,
            'json' => $payload,
        ]);

        $this->assertSame(201, $response->getStatusCode());

        return $response->toArray()['@id'];
    }

    private function createWrittenSourceCitedWork(string $token): string
    {
        $client = self::createClient();
        $writtenSourceIri = $this->createWrittenSource($token);
        $citedWork = $this->getVocabulary('history/cited_works')[0];

        $payload = [
            'writtenSource' => $writtenSourceIri,
            'citedWork' => $citedWork['@id'],
            'yearCompleted' => 2023,
        ];

        $response = $this->apiRequest($client, 'POST', '/api/data/history/written_sources_cited_works', [
            'token' => $token,
            'json' => $payload,
        ]);

        $this->assertSame(201, $response->getStatusCode());

        return $response->toArray()['@id'];
    }

    private function getIdFromIri(string $iri): int
    {
        $parts = explode('/', $iri);

        return (int) end($parts);
    }
}
