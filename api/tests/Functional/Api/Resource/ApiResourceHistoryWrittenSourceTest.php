<?php

namespace App\Tests\Functional\Api\Resource;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Tests\Functional\Api\ApiTestProviderTrait;
use App\Tests\Functional\ApiTestRequestTrait;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class ApiResourceHistoryWrittenSourceTest extends ApiTestCase
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

        $collectionResponse = $this->apiRequest($client, 'GET', '/api/data/history/written_sources');
        $collection = $collectionResponse->toArray();
        $this->assertArrayHasKey('_acl', $collection);
        $this->assertFalse($collection['_acl']['canCreate']);
    }

    public function testPostGetCollectionWholeAclReturnsTrueForAdminUser(): void
    {
        $client = self::createClient();
        $token = $this->getUserToken($client, 'user_admin');

        $collectionResponse = $this->apiRequest($client, 'GET', '/api/data/history/written_sources', ['token' => $token]);
        $collection = $collectionResponse->toArray();
        $this->assertArrayHasKey('_acl', $collection);
        $this->assertTrue($collection['_acl']['canCreate']);
    }

    public function testPostGetCollectionWholeAclReturnsTrueForHistorianUser(): void
    {
        $client = self::createClient();
        $token = $this->getUserToken($client, 'user_his');

        $collectionResponse = $this->apiRequest($client, 'GET', '/api/data/history/written_sources', ['token' => $token]);
        $collection = $collectionResponse->toArray();
        $this->assertArrayHasKey('_acl', $collection);
        $this->assertTrue($collection['_acl']['canCreate']);
    }

    public function testPostWrittenSourceIsAllowedForHistorianUser(): void
    {
        $client = self::createClient();
        $token = $this->getUserToken($client, 'user_his');

        $author = $this->getVocabulary('history/authors')[0];
        $type = $this->getVocabulary('history/written_source_types')[0];

        $payload = [
            'author' => $author['@id'],
            'writtenSourceType' => $type['@id'],
            'title' => 'Test Written Source Title',
            'publicationDetails' => 'Test Publication Details',
            'notes' => 'Some test notes',
        ];

        $response = $this->apiRequest($client, 'POST', '/api/data/history/written_sources', [
            'token' => $token,
            'json' => $payload,
        ]);

        $this->assertSame(201, $response->getStatusCode());
        $responseData = $response->toArray();
        $this->assertSame($payload['title'], $responseData['title']);
    }

    public function testPatchWrittenSourceIsAllowedForHistorianUser(): void
    {
        $client = self::createClient();
        $token = $this->getUserToken($client, 'user_his');

        $writtenSourceIri = $this->createWrittenSource($token);
        $id = $this->getIdFromIri($writtenSourceIri);

        $payload = [
            'title' => 'Updated Title',
        ];

        $response = $this->apiRequest($client, 'PATCH', "/api/data/history/written_sources/$id", [
            'token' => $token,
            'json' => $payload,
        ]);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('Updated Title', $response->toArray()['title']);
    }

    public function testDeleteWrittenSourceIsAllowedForHistorianUser(): void
    {
        $client = self::createClient();
        $token = $this->getUserToken($client, 'user_his');

        $writtenSourceIri = $this->createWrittenSource($token);
        $id = $this->getIdFromIri($writtenSourceIri);

        $response = $this->apiRequest($client, 'DELETE', "/api/data/history/written_sources/$id", [
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
            'title' => 'Initial Title',
            'publicationDetails' => 'Initial Publication',
        ];

        $response = $this->apiRequest($client, 'POST', '/api/data/history/written_sources', [
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
