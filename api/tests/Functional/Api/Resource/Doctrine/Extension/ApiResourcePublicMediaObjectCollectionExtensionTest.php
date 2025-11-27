<?php

namespace App\Tests\Functional\Api\Resource\Doctrine\Extension;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Tests\Functional\Api\ApiTestProviderTrait;
use App\Tests\Functional\ApiTestRequestTrait;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class ApiResourcePublicMediaObjectCollectionExtensionTest extends ApiTestCase
{
    use ApiTestRequestTrait;
    use ApiTestProviderTrait;

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

    public function testGetCollectionReturnOnlyPublicMediaObjectForUnauthenticatedUser()
    {
        $client = self::createClient();
        //        $response = $this->apiRequest($client, 'GET', '/api/data/stratigraphic_units?mediaObjects.mediaObject.type.group=image');
        $response = $this->apiRequest($client, 'GET', 'http://localhost/api/data/potteries?exists[mediaObjects]=true');

        $this->assertSame(200, $response->getStatusCode());
    }
}
