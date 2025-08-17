<?php

namespace App\Tests\Functional\Api\Resource;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Tests\Functional\Api\ApiTestProviderTrait;
use App\Tests\Functional\ApiTestRequestTrait;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class ApiResourceContextPotteryTest extends ApiTestCase
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

    public function testPostCreatesPotterySuccess(): void
    {
        $client = self::createClient();
        $token = $this->getUserToken($client, 'user_pot');

        $site = $this->apiRequest($client, 'GET', '/api/data/sites?code=SE');
        $site = $site->toArray()['member'][0];

        $response = $this->apiRequest($client, 'GET', "/api/data/stratigraphic_units?site={$site['@id']}");
        $su = $response->toArray()['member'][0];

        $this->assertNotEmpty($su, 'Fixture stratigraphic unit should exist');

        $functionalGroups = $this->getVocabulary(['pottery', 'functional_groups']);
        $functionalForms = $this->getVocabulary(['pottery', 'functional_forms']);

        $payload = [
            'inventory' => 'test.'.uniqid(),
            'stratigraphicUnit' => $su['@id'],
            'functionalGroup' => $functionalGroups[0]['@id'],
            'functionalForm' => $functionalForms[0]['@id'],
            'notes' => 'Test notes',
        ];

        $response = $this->apiRequest($client, 'POST', '/api/data/potteries', [
            'token' => $token,
            'json' => $payload,
        ]);

        $this->assertSame(201, $response->getStatusCode());
        $createdData = $response->toArray();
        $this->assertEquals($payload['inventory'], $createdData['inventory']);
        $this->assertEquals($payload['notes'], $createdData['notes']);
        $this->assertEquals($payload['functionalGroup'], $createdData['functionalGroup']);
        $this->assertEquals($payload['functionalForm'], $createdData['functionalForm']);
        $this->assertEquals($payload['stratigraphicUnit'], $createdData['stratigraphicUnit']['@id']);
    }

    public function testPostCreatesPotteryIsDeniedIfMissingSitePrivileges(): void
    {
        $client = self::createClient();
        $token = $this->getUserToken($client, 'user_pot');

        $site = $this->apiRequest($client, 'GET', '/api/data/sites?code=TO');
        $site = $site->toArray()['member'][0];

        $response = $this->apiRequest($client, 'GET', "/api/data/stratigraphic_units?site={$site['@id']}");
        $su = $response->toArray()['member'][0];

        $this->assertNotEmpty($su, 'Fixture stratigraphic unit should exist');

        $functionalGroups = $this->getVocabulary(['pottery', 'functional_groups']);
        $functionalForms = $this->getVocabulary(['pottery', 'functional_forms']);

        $payload = [
            'inventory' => 'test.'.uniqid(),
            'stratigraphicUnit' => $su['@id'],
            'functionalGroup' => $functionalGroups[0]['@id'],
            'functionalForm' => $functionalForms[0]['@id'],
            'notes' => 'Test notes',
        ];

        $response = $this->apiRequest($client, 'POST', '/api/data/potteries', [
            'token' => $token,
            'json' => $payload,
        ]);

        $this->assertSame(403, $response->getStatusCode());
    }

    public function testPostCreatesPotteryIsDeniedIfMissingSpecialistRole(): void
    {
        $client = self::createClient();
        $token = $this->getUserToken($client, 'user_base');

        $site = $this->apiRequest($client, 'GET', '/api/data/sites?code=TO');
        $site = $site->toArray()['member'][0];

        $response = $this->apiRequest($client, 'GET', "/api/data/stratigraphic_units?site={$site['@id']}");
        $su = $response->toArray()['member'][0];

        $this->assertNotEmpty($su, 'Fixture stratigraphic unit should exist');

        $functionalGroups = $this->getVocabulary(['pottery', 'functional_groups']);
        $functionalForms = $this->getVocabulary(['pottery', 'functional_forms']);

        $payload = [
            'inventory' => 'test.'.uniqid(),
            'stratigraphicUnit' => $su['@id'],
            'functionalGroup' => $functionalGroups[0]['@id'],
            'functionalForm' => $functionalForms[0]['@id'],
            'notes' => 'Test notes',
        ];

        $response = $this->apiRequest($client, 'POST', '/api/data/potteries', [
            'token' => $token,
            'json' => $payload,
        ]);

        $this->assertSame(403, $response->getStatusCode());
    }
}
