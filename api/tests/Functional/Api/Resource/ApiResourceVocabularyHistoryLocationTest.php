<?php

namespace App\Tests\Functional\Api\Resource;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Tests\Functional\ApiTestRequestTrait;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class ApiResourceVocabularyHistoryLocationTest extends ApiTestCase
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

    public function testCreateDeleteItem(): void
    {
        $client = self::createClient();

        $token = $this->getUserToken($client, 'user_admin');

        $value = 'value '.uniqid();

        $response = $this->apiRequest($client, 'POST', '/api/vocabulary/history/locations', [
            'token' => $token,
            'json' => [
                'value' => $value,
                'n' => 10,
                'e' => 20,
            ],
        ]);

        $this->assertSame(201, $response->getStatusCode());
        $responseData = $response->toArray();
        $this->assertSame($value, $responseData['value']);
        $this->assertSame(10, $responseData['n']);
        $this->assertSame(20, $responseData['e']);

        $response = $this->apiRequest($client, 'DELETE', $responseData['@id'], [
            'token' => $token,
        ]);

        $this->assertSame(204, $response->getStatusCode());
    }

    public function testDeleteParentLocationFails(): void
    {
        $client = self::createClient();

        $token = $this->getUserToken($client, 'user_admin');
        $siteResponse = $this->apiRequest($client, 'GET', '/api/vocabulary/history/locations?order[id]=asc&search=tortosa', [
            'token' => $token,
        ]);
        $this->assertSame(200, $siteResponse->getStatusCode());
        $siteData = $siteResponse->toArray();
        $this->assertArrayHasKey('member', $siteData);
        $this->assertGreaterThan(0, count($siteData['member']));
        $locationId = $siteData['member'][0]['@id'];

        $deleteResponse = $this->apiRequest($client, 'DELETE', $locationId, [
            'token' => $token,
        ]);
        $this->assertSame(422, $deleteResponse->getStatusCode());
    }
}
