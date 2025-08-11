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

        $uploadedFile = $this->getTestUploadFile('simple-text.txt');

        $response = $this->apiRequest($client, 'POST', '/api/data/media_objects', [
            'headers' => ['Content-Type' => 'multipart/form-data'],
            'json' => [
                'description' => 'The media object description',
            ],
            'extra' => [
                'files' => [
                    'file' => $uploadedFile,
                ],
            ],
        ]);

        $this->assertSame(201, $response->getStatusCode());
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
    }
}
