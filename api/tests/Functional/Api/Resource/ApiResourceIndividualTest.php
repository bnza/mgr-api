<?php

namespace App\Tests\Functional\Api\Resource;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Tests\Functional\Api\ApiTestProviderTrait;
use App\Tests\Functional\ApiTestRequestTrait;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class ApiResourceIndividualTest extends ApiTestCase
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

    public function testPostCreatesIndividualSuccess(): void
    {
        $client = self::createClient();
        $token = $this->getUserToken($client, 'user_ant');

        $site = $this->apiRequest($client, 'GET', '/api/data/archaeological_sites?code=TO');
        $site = $site->toArray()['member'][0];

        $response = $this->apiRequest($client, 'GET', "/api/data/stratigraphic_units?site={$site['@id']}");
        $su = $response->toArray()['member'][0];

        $this->assertNotEmpty($su, 'Fixture stratigraphic unit should exist');

        $ages = $this->getVocabulary(['individual', 'age']);

        $payload = [
            'identifier' => 'test.'.uniqid(),
            'stratigraphicUnit' => $su['@id'],
            'age' => $ages[0]['@id'],
            'sex' => 'F',
            'notes' => 'Test notes',
        ];

        $response = $this->apiRequest($client, 'POST', '/api/data/individuals', [
            'token' => $token,
            'json' => $payload,
        ]);

        $this->assertSame(201, $response->getStatusCode());
        $createdData = $response->toArray();
        $this->assertEquals($payload['identifier'], $createdData['identifier']);
        $this->assertEquals($payload['notes'], $createdData['notes']);
        $this->assertEquals($payload['age'], $createdData['age']);
        $this->assertEquals($payload['sex'], $createdData['sex']);
        $this->assertEquals($payload['stratigraphicUnit'], $createdData['stratigraphicUnit']['@id']);
    }

    public function testPostCreatesIndividualIsDeniedIfMissingSitePrivileges(): void
    {
        $client = self::createClient();
        $token = $this->getUserToken($client, 'user_ant');

        // TO site is usually restricted for user_ant if it follows the same pattern as user_pot
        $site = $this->apiRequest($client, 'GET', '/api/data/archaeological_sites?code=SE');
        $site = $site->toArray()['member'][0];

        $response = $this->apiRequest($client, 'GET', "/api/data/stratigraphic_units?site={$site['@id']}");
        $su = $response->toArray()['member'][0];

        $this->assertNotEmpty($su, 'Fixture stratigraphic unit should exist');

        $ages = $this->getVocabulary(['individual', 'age']);

        $payload = [
            'identifier' => 'test.'.uniqid(),
            'stratigraphicUnit' => $su['@id'],
            'age' => $ages[0]['@id'],
            'sex' => 'M',
            'notes' => 'Test notes',
        ];

        $response = $this->apiRequest($client, 'POST', '/api/data/individuals', [
            'token' => $token,
            'json' => $payload,
        ]);

        $this->assertSame(403, $response->getStatusCode());
    }

    public function testPostCreatesIndividualIsDeniedIfMissingSpecialistRole(): void
    {
        $client = self::createClient();
        $token = $this->getUserToken($client, 'user_base');

        $site = $this->apiRequest($client, 'GET', '/api/data/archaeological_sites?code=TO');
        $site = $site->toArray()['member'][0];

        $response = $this->apiRequest($client, 'GET', "/api/data/stratigraphic_units?site={$site['@id']}");
        $su = $response->toArray()['member'][0];

        $this->assertNotEmpty($su, 'Fixture stratigraphic unit should exist');

        $ages = $this->getVocabulary(['individual', 'age']);

        $payload = [
            'identifier' => 'test.'.uniqid(),
            'stratigraphicUnit' => $su['@id'],
            'age' => $ages[0]['@id'],
            'sex' => '?',
            'notes' => 'Test notes',
        ];

        $response = $this->apiRequest($client, 'POST', '/api/data/individuals', [
            'token' => $token,
            'json' => $payload,
        ]);

        $this->assertSame(403, $response->getStatusCode());
    }

    public function testPostCreatesIndividualDuplicateIdentifierReturnsUnprocessableEntity(): void
    {
        $client = self::createClient();
        $token = $this->getUserToken($client, 'user_ant');

        $site = $this->apiRequest($client, 'GET', '/api/data/archaeological_sites?code=TO');
        $site = $site->toArray()['member'][0];

        $response = $this->apiRequest($client, 'GET', "/api/data/stratigraphic_units?site={$site['@id']}");
        $su = $response->toArray()['member'][0];

        $ages = $this->getVocabulary(['individual', 'age']);

        $identifier = 'duplicate.'.uniqid();

        $payload = [
            'identifier' => $identifier,
            'stratigraphicUnit' => $su['@id'],
            'age' => $ages[0]['@id'],
        ];

        // First creation succeeds
        $this->apiRequest($client, 'POST', '/api/data/individuals', [
            'token' => $token,
            'json' => $payload,
        ]);
        $this->assertResponseIsSuccessful();

        // Second creation with same identifier and same SU (same site) fails
        $response = $this->apiRequest($client, 'POST', '/api/data/individuals', [
            'token' => $token,
            'json' => $payload,
        ]);

        $this->assertSame(422, $response->getStatusCode());
        $data = $response->toArray(false);
        $this->assertArrayHasKey('violations', $data);
        $this->assertStringContainsString('unique', $data['violations'][0]['message']);
    }
}
