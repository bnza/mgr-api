<?php

namespace App\Tests\Functional\Api\Resource\Validator;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Entity\Data\Analysis;
use App\Entity\Data\Join\Analysis\AnalysisBotanyCharcoal;
use App\Entity\Data\Join\Analysis\AnalysisBotanySeed;
use App\Entity\Data\Join\Analysis\AnalysisContextBotany;
use App\Entity\Data\Join\Analysis\AnalysisContextZoo;
use App\Entity\Data\Join\Analysis\AnalysisIndividual;
use App\Entity\Data\Join\Analysis\AnalysisPottery;
use App\Entity\Data\Join\Analysis\AnalysisSampleMicrostratigraphy;
use App\Entity\Data\Join\Analysis\AnalysisSiteAnthropology;
use App\Entity\Data\Join\Analysis\AnalysisZooBone;
use App\Entity\Data\Join\Analysis\AnalysisZooTooth;
use App\Entity\Data\Join\Analysis\BaseAnalysisJoin;
use App\Tests\Functional\Api\ApiTestProviderTrait;
use App\Tests\Functional\ApiTestRequestTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class ValidatorPermittedAnalysisTypeTest extends ApiTestCase
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

    /**
     * @return list<array{0: class-string<BaseAnalysisJoin>, 1: string, 2: string}>
     */
    private static function getAnalysisJoinSubclasses(): array
    {
        return [
            [AnalysisBotanyCharcoal::class, '/api/data/botany/charcoals', '/api/data/analyses/botany/charcoals'],
            [AnalysisBotanySeed::class, '/api/data/botany/seeds', '/api/data/analyses/botany/seeds'],
            [AnalysisContextBotany::class, '/api/data/contexts', '/api/data/analyses/contexts/botany'],
            [AnalysisContextZoo::class, '/api/data/contexts', '/api/data/analyses/contexts/zoo'],
            [AnalysisIndividual::class, '/api/data/individuals', '/api/data/analyses/individuals'],
            [AnalysisSampleMicrostratigraphy::class, '/api/data/samples', '/api/data/analyses/samples/microstratigraphy'],
            [AnalysisSiteAnthropology::class, '/api/data/archaeological_sites', '/api/data/analyses/archaeological_sites/anthropology'],
            [AnalysisPottery::class, '/api/data/potteries', '/api/data/analyses/potteries'],
            [AnalysisZooBone::class, '/api/data/zoo/bones', '/api/data/analyses/zoo/bones'],
            [AnalysisZooTooth::class, '/api/data/zoo/teeth', '/api/data/analyses/zoo/teeth'],
        ];
    }

    public static function getAnalysisJoinSubclassesUnpermittedAnalysisTypesProvider(): array
    {
        $return = [];
        $types = array_keys(Analysis::TYPES);
        foreach (self::getAnalysisJoinSubclasses() as [$joinClass, $subjectPath, $joinPath]) {
            $unpermittedTypes = array_diff($types, $joinClass::getPermittedAnalysisTypes());
            foreach ($unpermittedTypes as $type) {
                $return[sprintf(
                    '%s:%s',
                    basename(str_replace('\\', '/', $joinClass)),
                    $type)] = [$joinClass, $subjectPath, $joinPath, $type];
            }
        }

        return $return;
    }

    public static function getAnalysisJoinSubclassesPermittedAnalysisTypesProvider(): array
    {
        $return = [];
        foreach (self::getAnalysisJoinSubclasses() as [$joinClass, $subjectPath, $joinPath]) {
            foreach ($joinClass::getPermittedAnalysisTypes() as $type) {
                $return[sprintf(
                    '%s:%s',
                    basename(str_replace('\\', '/', $joinClass)),
                    $type)] = [$joinClass, $subjectPath, $joinPath, $type];
            }
        }

        return $return;
    }

    #[DataProvider('getAnalysisJoinSubclassesUnpermittedAnalysisTypesProvider')]
    public function testPermittedAnalysisTypeRejectsDisallowedTypeOnSubjectJoin(string $joinClass, string $subjectPath, string $joinPath, string $type): void
    {
        $client = static::createClient();
        $token = $this->getUserToken($client, 'user_admin');

        // 1) Pick a subject
        $subjectResponse = $this->apiRequest($client, 'GET', $subjectPath, []);
        $this->assertSame(200, $subjectResponse->getStatusCode());
        $subjects = $subjectResponse->toArray()['member'] ?? [];
        $this->assertNotEmpty($subjects, "Expected at least one $joinClass subject from fixtures");
        $subjectIri = $subjects[0]['@id'];

        // 2) Create a new Analysis with a disallowed type for the subject
        $analysisTypes = $this->getVocabulary(['analysis', 'types']);
        $this->assertNotEmpty($analysisTypes, 'Expected analysis types to be available');

        $analysisType = array_find($analysisTypes, fn ($t) => ($t['code'] ?? null) === $type);
        $this->assertNotNull($analysisType, "Expected to find '$type' analysis type in vocabulary");

        $analysisIdentifier = "TEST.$type.".uniqid();
        $createAnalysisResponse = $this->apiRequest($client, 'POST', '/api/data/analyses', [
            'token' => $token,
            'json' => [
                'type' => $analysisType['@id'] ?? $analysisType['id'],
                'year' => 2023,
                'identifier' => $analysisIdentifier,
            ],
        ]);

        $this->assertSame(201, $createAnalysisResponse->getStatusCode(), 'Expected analysis creation to succeed');
        $analysis = $createAnalysisResponse->toArray();
        $analysisIri = $analysis['@id'];

        // 3) Attempt to create a Botany Charcoal join with the disallowed analysis type
        $createJoinResponse = $this->apiRequest($client, 'POST', $joinPath, [
            'token' => $token,
            'json' => [
                'analysis' => $analysisIri,
                'subject' => $subjectIri,
            ],
        ]);

        $this->assertSame(422, $createJoinResponse->getStatusCode(), 'Expected validation to fail for disallowed analysis type');
        $data = $createJoinResponse->toArray(false);
        $this->assertArrayHasKey('violations', $data, 'Expected violations in response');
        $messages = array_map(fn ($v) => $v['message'] ?? '', $data['violations']);
        $this->assertTrue(
            (bool) array_filter($messages, fn ($m) => str_contains($m, 'not permitted')),
            'Expected a violation message indicating the analysis type is not permitted'
        );
    }

    public static function getAnalysisBotanyCharcoalPermittedAnalysisTypesProvider(): array
    {
        $return = [];
        foreach (AnalysisBotanyCharcoal::getPermittedAnalysisTypes() as $type) {
            $return[$type] = [$type];
        }

        return $return;
    }

    #[DataProvider('getAnalysisJoinSubclassesPermittedAnalysisTypesProvider')]
    public function testPermittedAnalysisTypeAcceptsAllowedTypeOnSubjectJoin(string $joinClass, string $subjectPath, string $joinPath, string $type): void
    {
        $client = static::createClient();
        $token = $this->getUserToken($client, 'user_admin');

        // 1) Pick a subject
        $subjectResponse = $this->apiRequest($client, 'GET', $subjectPath, []);
        $this->assertSame(200, $subjectResponse->getStatusCode());
        $subjects = $subjectResponse->toArray()['member'] ?? [];
        $this->assertNotEmpty($subjects, "Expected at least one $joinClass subject from fixtures");
        $subjectIri = $subjects[0]['@id'];

        // 2) Create a new Analysis with an allowed type for the subject
        $analysisTypes = $this->getVocabulary(['analysis', 'types']);
        $this->assertNotEmpty($analysisTypes, 'Expected analysis types to be available');

        $analysisType = array_find($analysisTypes, fn ($t) => ($t['code'] ?? null) === $type);
        $this->assertNotNull($analysisType, "Expected to find $type analysis type in vocabulary");

        $analysisIdentifier = "TEST.$type.".uniqid();
        $createAnalysisResponse = $this->apiRequest($client, 'POST', '/api/data/analyses', [
            'token' => $token,
            'json' => [
                'type' => $analysisType['@id'] ?? $analysisType['id'],
                'year' => 2023,
                'identifier' => $analysisIdentifier,
            ],
        ]);

        $this->assertSame(201, $createAnalysisResponse->getStatusCode(), 'Expected analysis creation to succeed');
        $analysis = $createAnalysisResponse->toArray();
        $analysisIri = $analysis['@id'];

        // 3) Create a Botany Charcoal join with the allowed analysis type
        $createJoinResponse = $this->apiRequest($client, 'POST', $joinPath, [
            'token' => $token,
            'json' => [
                'analysis' => $analysisIri,
                'subject' => $subjectIri,
            ],
        ]);

        $this->assertSame(201, $createJoinResponse->getStatusCode(), 'Expected join creation to succeed for permitted analysis type');
        $created = $createJoinResponse->toArray();
        $this->assertArrayHasKey('@id', $created);
        $this->assertArrayHasKey('analysis', $created);
        $this->assertArrayHasKey('subject', $created);
    }
}
