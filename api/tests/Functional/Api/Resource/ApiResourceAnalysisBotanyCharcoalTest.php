<?php

namespace App\Tests\Functional\Api\Resource;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Tests\Functional\Api\ApiTestProviderTrait;
use App\Tests\Functional\ApiTestRequestTrait;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class ApiResourceAnalysisBotanyCharcoalTest extends ApiTestCase
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

        $collectionResponse = $this->apiRequest($client, 'GET', '/api/data/analyses/botany/charcoals');
        $collection = $collectionResponse->toArray();
        $this->arrayHasKey('_acl', $collection);
        $this->assertFalse($collection['_acl']['canCreate']);
    }

    public function testPostGetCollectionWholeAclReturnsTrueForAdminUser(): void
    {
        $client = self::createClient();
        $token = $this->getUserToken($client, 'user_admin');

        $collectionResponse = $this->apiRequest($client, 'GET', '/api/data/analyses/botany/charcoals', ['token' => $token]);
        $collection = $collectionResponse->toArray();
        $this->arrayHasKey('_acl', $collection);
        $this->assertTrue($collection['_acl']['canCreate']);
    }

    public function testPostGetCollectionWholeAclReturnsTrueForArchaeobotanyUser(): void
    {
        $client = self::createClient();
        $token = $this->getUserToken($client, 'user_bot');

        $collectionResponse = $this->apiRequest($client, 'GET', '/api/data/analyses/botany/charcoals', ['token' => $token]);
        $collection = $collectionResponse->toArray();
        $this->arrayHasKey('_acl', $collection);
        $this->assertTrue($collection['_acl']['canCreate']);
    }

    public function testPostGetCollectionWholeAclReturnsTrueForSpecialistUser(): void
    {
        $client = self::createClient();
        $token = $this->getUserToken($client, 'user_pot');

        $collectionResponse = $this->apiRequest($client, 'GET', '/api/data/analyses/botany/charcoals', ['token' => $token]);
        $collection = $collectionResponse->toArray();
        $this->arrayHasKey('_acl', $collection);
        $this->assertTrue($collection['_acl']['canCreate']);
    }

    public function testPostGetCollectionWholeAclReturnsFalseForNonSpecialistUser(): void
    {
        $client = self::createClient();
        $token = $this->getUserToken($client, 'user_base');

        $collectionResponse = $this->apiRequest($client, 'GET', '/api/data/analyses/botany/charcoals', ['token' => $token]);
        $collection = $collectionResponse->toArray();
        $this->arrayHasKey('_acl', $collection);
        $this->assertFalse($collection['_acl']['canCreate']);
    }

    public function testPostGetCollectionParentSubjectAclReturnsTrueForAdminUser(): void
    {
        $client = self::createClient();
        $token = $this->getUserToken($client, 'user_admin');

        $siteResponse = $this->apiRequest($client, 'GET', '/api/data/archaeological_sites?code=TO');
        $site = $siteResponse->toArray()['member'][0];

        $subjectResponse = $this->apiRequest($client, 'GET', "/api/data/botany/charcoals?stratigraphicUnit.site={$site['@id']}");
        $subject = $subjectResponse->toArray()['member'][0];

        $this->assertNotNull($subject, 'Fixture subject unit should exist');
        $subjectId = basename($subject['@id']);

        $collectionResponse = $this->apiRequest($client, 'GET', "/api/data/botany/charcoals/$subjectId/analyses", ['token' => $token]);
        $collection = $collectionResponse->toArray();

        $this->arrayHasKey('_acl', $collection);
        $this->assertTrue($collection['_acl']['canCreate']);
    }

    public function testPostGetCollectionParentSubjectAclReturnsTrueForArchaeobotanyUser(): void
    {
        $client = self::createClient();
        $token = $this->getUserToken($client, 'user_bot');

        $siteResponse = $this->apiRequest($client, 'GET', '/api/data/archaeological_sites?code=TO');
        $site = $siteResponse->toArray()['member'][0];

        $subjectResponse = $this->apiRequest($client, 'GET', "/api/data/botany/charcoals?stratigraphicUnit.site={$site['@id']}");
        $subject = $subjectResponse->toArray()['member'][0];

        $this->assertNotNull($subject, 'Fixture subject unit should exist');
        $subjectId = basename($subject['@id']);

        $collectionResponse = $this->apiRequest($client, 'GET', "/api/data/botany/charcoals/$subjectId/analyses", ['token' => $token]);
        $collection = $collectionResponse->toArray();

        $this->arrayHasKey('_acl', $collection);
        $this->assertTrue($collection['_acl']['canCreate']);
    }

    public function testPostGetCollectionParentSubjectAclReturnsFalseForNonArchaeobotanyUser(): void
    {
        $client = self::createClient();
        $token = $this->getUserToken($client, 'user_pot');

        $siteResponse = $this->apiRequest($client, 'GET', '/api/data/archaeological_sites?code=TO');
        $site = $siteResponse->toArray()['member'][0];

        $subjectResponse = $this->apiRequest($client, 'GET', "/api/data/botany/charcoals?stratigraphicUnit.site={$site['@id']}");
        $subject = $subjectResponse->toArray()['member'][0];

        $this->assertNotNull($subject, 'Fixture subject unit should exist');
        $subjectId = basename($subject['@id']);

        $collectionResponse = $this->apiRequest($client, 'GET', "/api/data/botany/charcoals/$subjectId/analyses", ['token' => $token]);
        $collection = $collectionResponse->toArray();

        $this->arrayHasKey('_acl', $collection);
        $this->assertFalse($collection['_acl']['canCreate']);
    }
}
