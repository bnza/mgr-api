<?php

namespace App\Tests\Functional\Api\Resource;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Tests\Functional\ApiTestRequestTrait;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class ApiResourcePotteryTest extends ApiTestCase
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

    public function testPotteryCreateDecorationsAreCreatedAndPatchedCorrectly(): void
    {
        $client = self::createClient();
        $token = $this->getUserToken($client, 'user_pot');

        $site = $this->apiRequest($client, 'GET', '/api/data/sites?code=SE');
        $site = $site->toArray()['member'][0];

        $response = $this->apiRequest($client, 'GET', "/api/data/stratigraphic_units?site={$site['@id']}");
        $su = $response->toArray()['member'][0];

        $functionalGroups = $this->getVocabulary(['pottery', 'functional_groups']);
        $functionalForms = $this->getVocabulary(['pottery', 'functional_forms']);
        $decorations = $this->getVocabulary(['pottery', 'decorations']);

        $payload = [
            'inventory' => 'test.decorations.'.uniqid(),
            'stratigraphicUnit' => $su['@id'],
            'functionalGroup' => $functionalGroups[0]['@id'],
            'functionalForm' => $functionalForms[0]['@id'],
            'decorations' => [
                $decorations[0]['@id'],
                $decorations[1]['@id'],
            ],
        ];

        $potteryResponse = $this->apiRequest($client, 'POST', '/api/data/potteries', [
            'token' => $token,
            'json' => $payload,
        ]);

        $this->assertSame(201, $potteryResponse->getStatusCode());
        $potteryData = $potteryResponse->toArray();
        $this->assertArrayHasKey('decorations', $potteryData);
        $this->assertCount(2, $potteryData['decorations']);
        $this->assertSame($decorations[0]['@id'], $potteryData['decorations'][0]['@id']);
        $this->assertSame($decorations[1]['@id'], $potteryData['decorations'][1]['@id']);

        $potteryResponse = $this->apiRequest($client, 'PATCH', $potteryData['@id'], [
            'token' => $token,
            'json' => [
                'decorations' => [
                    $decorations[0]['@id'],
                    $decorations[2]['@id'],
                    $decorations[3]['@id'],
                ],
            ],
        ]);

        $this->assertSame(200, $potteryResponse->getStatusCode());
        $potteryData = $potteryResponse->toArray();
        $this->assertArrayHasKey('decorations', $potteryData);
        $this->assertCount(3, $potteryData['decorations']);
        $this->assertSame($decorations[0]['@id'], $potteryData['decorations'][0]['@id']);
        $this->assertSame($decorations[2]['@id'], $potteryData['decorations'][1]['@id']);
        $this->assertSame($decorations[3]['@id'], $potteryData['decorations'][2]['@id']);
    }
}
