<?php

namespace App\Tests\Functional\Api\Resource;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Tests\Functional\ApiTestFileUploadTrait;
use App\Tests\Functional\ApiTestRequestTrait;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class ApiResourceMediaObjectTest extends ApiTestCase
{
    use ApiTestRequestTrait;
    use ApiTestFileUploadTrait;

    protected function setUp(): void
    {
        parent::setUp();
        static::$alwaysBootKernel = false;
        $container = self::getContainer();
        $this->parameterBag = $container->get(ParameterBagInterface::class);
        $this->setUpTestFileUpload($container);
    }

    protected function tearDown(): void
    {
        $this->parameterBag = null;
        parent::tearDown();
    }

    public function testUploadTextFile(): void
    {
        $client = static::createClient();

        $token = $this->getUserToken($client, 'user_base');

        $uploadedFile = $this->getTestUploadFile('simple-text.txt');

        $types = $this->apiRequest($client, 'GET', '/api/vocabulary/media_object/types')->toArray();

        $type = $types['member'][0]['@id'];

        $response = $this->apiRequest($client, 'POST', '/api/data/media_objects', [
            'token' => $token,
            'headers' => ['Content-Type' => 'multipart/form-data'],
            'extra' => [
                'parameters' => [
                    'type' => $type,
                    'description' => 'The media object description',
                ],
                'files' => [
                    'file' => $uploadedFile,
                ],
            ],
        ]);

        $this->assertSame(201, $response->getStatusCode());
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
    }

    public function testGetMediaObjectBySha256(): void
    {
        $client = static::createClient();

        $token = $this->getUserToken($client, 'user_base');

        // First, get the collection to retrieve an existing SHA256
        $collectionResponse = $this->apiRequest($client, 'GET', '/api/data/media_objects', [
            'token' => $token,
        ]);

        $this->assertSame(200, $collectionResponse->getStatusCode());

        $collectionData = $collectionResponse->toArray();
        $firstMediaObject = $collectionData['member'][0];

        $sha256 = $firstMediaObject['sha256'];
        $expectedId = $firstMediaObject['id'];

        // Now test id still works
        $response = $this->apiRequest($client, 'GET', "/api/data/media_objects/{$expectedId}", [
            'token' => $token,
        ]);

        $this->assertSame(200, $response->getStatusCode());
        $mediaObject = $response->toArray();
        // Verify we got the same media object
        $this->assertSame($expectedId, $mediaObject['id'], 'Retrieved media object ID does not match expected ID');
        $this->assertSame($sha256, $mediaObject['sha256'], 'Retrieved media object SHA256 does not match requested SHA256');

        // Now test the SHA256 endpoint
        $response = $this->apiRequest($client, 'GET', "/api/data/media_objects/{$sha256}", [
            'token' => $token,
        ]);

        $this->assertSame(200, $response->getStatusCode());

        $mediaObject = $response->toArray();

        // Verify we got the same media object
        $this->assertSame($expectedId, $mediaObject['id'], 'Retrieved media object ID does not match expected ID');
        $this->assertSame($sha256, $mediaObject['sha256'], 'Retrieved media object SHA256 does not match requested SHA256');
    }
}
