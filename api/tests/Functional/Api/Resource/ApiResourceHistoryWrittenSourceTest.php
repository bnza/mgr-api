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

    public function testFilterUnaccentedTitleGetCollection(): void
    {
        $client = self::createClient();

        // "al-Talqīn fī l-fiqh al-mālikī" should match when searching "talqin"
        $response = $this->apiRequest($client, 'GET', '/api/data/history/written_sources?title=talqin');
        $this->assertSame(200, $response->getStatusCode());
        $data = $response->toArray();
        $this->assertGreaterThanOrEqual(1, count($data['member']));
        $this->assertStringContainsStringIgnoringCase('Talqīn', $data['member'][0]['title']);

        // Same search with transliterated chars should also match
        $response = $this->apiRequest($client, 'GET', '/api/data/history/written_sources?title=Talqīn');
        $this->assertSame(200, $response->getStatusCode());
        $data = $response->toArray();
        $this->assertGreaterThanOrEqual(1, count($data['member']));
    }

    public function testFilterUnaccentedSubtitleGetCollection(): void
    {
        $client = self::createClient();

        // "Tratado de agricultura" should match when searching "agricultura"
        $response = $this->apiRequest($client, 'GET', '/api/data/history/written_sources?subtitle=agricultura');
        $this->assertSame(200, $response->getStatusCode());
        $data = $response->toArray();
        $this->assertGreaterThanOrEqual(1, count($data['member']));
        $this->assertStringContainsStringIgnoringCase('agricultura', $data['member'][0]['subtitle']);
    }

    public function testFilterUnaccentedPublicationDetailsGetCollection(): void
    {
        $client = self::createClient();

        // "Beirut: Dār al-kutub al-ʽilmiyya" should match when searching "ilmiyya"
        $response = $this->apiRequest($client, 'GET', '/api/data/history/written_sources?publicationDetails=ilmiyya');
        $this->assertSame(200, $response->getStatusCode());
        $data = $response->toArray();
        $this->assertGreaterThanOrEqual(1, count($data['member']));
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

    public function testDeleteWrittenSourceWithCitedWorksIsRestricted(): void
    {
        $client = self::createClient();
        $token = $this->getUserToken($client, 'user_his');

        // Create a written source
        $writtenSourceIri = $this->createWrittenSource($token);

        // Add a cited work to it
        $citedWork = $this->getVocabulary('history/cited_works')[0];
        $this->apiRequest($client, 'POST', '/api/data/history/written_sources_cited_works', [
            'token' => $token,
            'json' => [
                'writtenSource' => $writtenSourceIri,
                'citedWork' => $citedWork['@id'],
                'yearCompleted' => 2025,
            ],
        ]);
        $this->assertResponseIsSuccessful();

        // Attempt to delete the written source
        $id = $this->getIdFromIri($writtenSourceIri);
        $response = $this->apiRequest($client, 'DELETE', "/api/data/history/written_sources/$id", [
            'token' => $token,
        ]);

        // It should be restricted (fail)
        // If cascade remove is on, this will return 204.
        // If RESTRICT is working, it should return 500 or 409 depending on how it's handled.
        // After fix, it should return 422 because of NotReferenced constraint.
        $this->assertNotSame(204, $response->getStatusCode(), 'Deleting a WrittenSource with CitedWorks should be restricted.');
        $this->assertSame(422, $response->getStatusCode());
    }

    public function testWrittenSourceCreateCenturiesAreCreatedAndPatchedCorrectly(): void
    {
        $client = self::createClient();
        $token = $this->getUserToken($client, 'user_his');

        $author = $this->getVocabulary('history/authors')[0];
        $type = $this->getVocabulary('history/written_source_types')[0];

        $payload = [
            'author' => $author['@id'],
            'writtenSourceType' => $type['@id'],
            'title' => 'Test Written Source with Centuries',
            'publicationDetails' => 'Test Publication Details',
            'centuries' => [
                '/api/vocabulary/centuries/700',
                '/api/vocabulary/centuries/900',
            ],
        ];

        $response = $this->apiRequest($client, 'POST', '/api/data/history/written_sources', [
            'token' => $token,
            'json' => $payload,
        ]);

        $this->assertSame(201, $response->getStatusCode());
        $responseData = $response->toArray();
        $this->assertArrayHasKey('centuries', $responseData);
        $this->assertCount(2, $responseData['centuries']);
        $this->assertSame('/api/vocabulary/centuries/700', $responseData['centuries'][0]['@id']);
        $this->assertSame('/api/vocabulary/centuries/900', $responseData['centuries'][1]['@id']);

        $response = $this->apiRequest($client, 'PATCH', $responseData['@id'], [
            'token' => $token,
            'json' => [
                'centuries' => [
                    '/api/vocabulary/centuries/700',
                    '/api/vocabulary/centuries/800',
                    '/api/vocabulary/centuries/1000',
                ],
            ],
        ]);

        $this->assertSame(200, $response->getStatusCode());
        $responseData = $response->toArray();
        $this->assertArrayHasKey('centuries', $responseData);
        $this->assertCount(3, $responseData['centuries']);
        $this->assertSame('/api/vocabulary/centuries/700', $responseData['centuries'][0]['@id']);
        $this->assertSame('/api/vocabulary/centuries/800', $responseData['centuries'][1]['@id']);
        $this->assertSame('/api/vocabulary/centuries/1000', $responseData['centuries'][2]['@id']);
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
