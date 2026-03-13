<?php

namespace App\Tests\Functional\Api\Resource;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Tests\Functional\ApiTestRequestTrait;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class ApiResourceMediaObjectHistoryLocationTest extends ApiTestCase
{
    use ApiTestRequestTrait;

    private ?ParameterBagInterface $parameterBag = null;

    protected function setUp(): void
    {
        parent::setUp();
        static::$alwaysBootKernel = false;
        $container = self::getContainer();
        $this->parameterBag = $container->get(ParameterBagInterface::class);
    }

    protected function tearDown(): void
    {
        $this->parameterBag = null;
        parent::tearDown();
    }

    public function testCreateSucceedForHistorian(): void
    {
        $client = static::createClient();

        $token = $this->getUserToken($client, 'user_his');

        $locations = $this->apiRequest($client, 'GET', '/api/vocabulary/history/locations', ['token' => $token])->toArray();
        $location = $locations['member'][0]['@id'];

        $media = $this->apiRequest($client, 'GET', '/api/data/media_objects', ['token' => $token])->toArray();
        $medium = $media['member'][0]['@id'];

        $response = $this->apiRequest($client, 'POST', '/api/data/media_object_history_locations', [
            'token' => $token,
            'json' => [
                'mediaObject' => $medium,
                'item' => $location,
            ],
        ]);

        $this->assertSame(201, $response->getStatusCode());
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
    }

    public function testCreateFailForNonHistorian(): void
    {
        $client = static::createClient();

        // user_pot is a CERAMIC_SPECIALIST, not a HISTORIAN or ADMIN
        $token = $this->getUserToken($client, 'user_pot');

        $locations = $this->apiRequest($client, 'GET', '/api/vocabulary/history/locations', ['token' => $token])->toArray();
        $location = $locations['member'][0]['@id'];

        $media = $this->apiRequest($client, 'GET', '/api/data/media_objects', ['token' => $token])->toArray();
        $medium = $media['member'][0]['@id'];

        $response = $this->apiRequest($client, 'POST', '/api/data/media_object_history_locations', [
            'token' => $token,
            'json' => [
                'mediaObject' => $medium,
                'item' => $location,
            ],
        ]);

        $this->assertSame(403, $response->getStatusCode());
    }
}
