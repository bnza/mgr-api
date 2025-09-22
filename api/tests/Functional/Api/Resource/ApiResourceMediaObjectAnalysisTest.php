<?php

namespace App\Tests\Functional\Api\Resource;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Tests\Functional\ApiTestFileUploadTrait;
use App\Tests\Functional\ApiTestRequestTrait;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class ApiResourceMediaObjectAnalysisTest extends ApiTestCase
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

    public function testCreateSucceedForTheAnalysisCreator(): void
    {
        $client = static::createClient();

        $token = $this->getUserToken($client, 'user_pot');

        $analyses = $this->apiRequest($client, 'GET', '/api/data/analyses', ['token' => $token])->toArray();

        $analyses = array_filter($analyses['member'], function ($analysis) {
            return 'user_pot@example.com' === $analysis['createdBy']['userIdentifier'];
        });

        $analysis = $analyses[array_key_first($analyses)]['@id'];

        $media = $this->apiRequest($client, 'GET', '/api/data/media_objects', ['token' => $token])->toArray();

        $medium = $media['member'][0]['@id'];

        $response = $this->apiRequest($client, 'POST', '/api/data/media_object_analyses', [
            'token' => $token,
            'json' => [
                'mediaObject' => $medium,
                'item' => $analysis,
            ],
        ]);

        $this->assertSame(201, $response->getStatusCode());
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
    }

    public function testCreateFailForTheAnalysisCreator(): void
    {
        $client = static::createClient();

        $token = $this->getUserToken($client, 'user_pot');

        $analyses = $this->apiRequest($client, 'GET', '/api/data/analyses', ['token' => $token])->toArray();

        $analyses = array_filter($analyses['member'], function ($analysis) {
            return 'user_pot@example.com' !== $analysis['createdBy']['userIdentifier'];
        });

        $analysis = $analyses[array_key_first($analyses)]['@id'];

        $media = $this->apiRequest($client, 'GET', '/api/data/media_objects', ['token' => $token])->toArray();

        $medium = $media['member'][0]['@id'];

        $response = $this->apiRequest($client, 'POST', '/api/data/media_object_analyses', [
            'token' => $token,
            'json' => [
                'mediaObject' => $medium,
                'item' => $analysis,
            ],
        ]);

        $this->assertSame(403, $response->getStatusCode());
    }
}
