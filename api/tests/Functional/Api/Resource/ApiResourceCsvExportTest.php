<?php

namespace App\Tests\Functional\Api\Resource;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Tests\Functional\ApiTestRequestTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class ApiResourceCsvExportTest extends ApiTestCase
{
    use ApiTestRequestTrait;

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

    public static function csvExportProvider(): array
    {
        return [
            // Top-level resources
            '/api/data/archaeological_sites' => ['/api/data/archaeological_sites', 'none'],
            '/api/data/stratigraphic_units' => ['/api/data/stratigraphic_units', 'none'],
            '/api/data/potteries' => ['/api/data/potteries', 'none'],
            '/api/data/samples' => ['/api/data/samples', 'none'],
            '/api/data/microstratigraphic_units' => ['/api/data/microstratigraphic_units', 'none'],
            '/api/data/contexts' => ['/api/data/contexts', 'none'],
            '/api/data/analyses' => ['/api/data/analyses', 'none'],
            '/api/data/individuals' => ['/api/data/individuals', 'none'],
            '/api/data/sediment_cores' => ['/api/data/sediment_cores', 'none'],
            '/api/data/zoo/bones' => ['/api/data/zoo/bones', 'none'],
            '/api/data/zoo/teeth' => ['/api/data/zoo/teeth', 'none'],
            '/api/data/botany/charcoals' => ['/api/data/botany/charcoals', 'none'],
            '/api/data/botany/seeds' => ['/api/data/botany/seeds', 'none'],
            '/api/data/context_stratigraphic_units' => ['/api/data/context_stratigraphic_units', 'none'],
            '/api/data/sample_stratigraphic_units' => ['/api/data/sample_stratigraphic_units', 'none'],
            '/api/data/sediment_core_depths' => ['/api/data/sediment_core_depths', 'none'],
            '/api/data/history/animals' => ['/api/data/history/animals', 'none'],
            '/api/data/history/plants' => ['/api/data/history/plants', 'none'],

            // Sub-resources requiring a parent id
            '/api/data/archaeological_sites/{parentId}/stratigraphic_units' => ['/api/data/archaeological_sites/{parentId}/stratigraphic_units', 'site'],
            '/api/data/stratigraphic_units/{parentId}/potteries' => ['/api/data/stratigraphic_units/{parentId}/potteries', 'su'],
            '/api/data/archaeological_sites/{parentId}/samples' => ['/api/data/archaeological_sites/{parentId}/samples', 'site'],
            '/api/data/stratigraphic_units/{parentId}/microstratigraphic_units' => ['/api/data/stratigraphic_units/{parentId}/microstratigraphic_units', 'su'],
            '/api/data/samples/{parentId}/microstratigraphic_units' => ['/api/data/samples/{parentId}/microstratigraphic_units', 'sample'],
            '/api/data/archaeological_sites/{parentId}/contexts' => ['/api/data/archaeological_sites/{parentId}/contexts', 'site'],
            '/api/data/stratigraphic_units/{parentId}/individuals' => ['/api/data/stratigraphic_units/{parentId}/individuals', 'su'],
            // '/api/data/archaeological_sites/{parentId}/sediment_cores' => ['/api/data/archaeological_sites/{parentId}/sediment_cores', 'site'],
            '/api/data/stratigraphic_units/{parentId}/zoo/bones' => ['/api/data/stratigraphic_units/{parentId}/zoo/bones', 'su'],
            '/api/data/stratigraphic_units/{parentId}/zoo/teeth' => ['/api/data/stratigraphic_units/{parentId}/zoo/teeth', 'su'],
            '/api/data/stratigraphic_units/{parentId}/botany/charcoals' => ['/api/data/stratigraphic_units/{parentId}/botany/charcoals', 'su'],
            '/api/data/stratigraphic_units/{parentId}/botany/seeds' => ['/api/data/stratigraphic_units/{parentId}/botany/seeds', 'su'],
            '/api/data/stratigraphic_units/{parentId}/contexts' => ['/api/data/stratigraphic_units/{parentId}/contexts', 'su'],
            '/api/data/contexts/{parentId}/stratigraphic_units' => ['/api/data/contexts/{parentId}/stratigraphic_units', 'context'],
            '/api/data/stratigraphic_units/{parentId}/samples' => ['/api/data/stratigraphic_units/{parentId}/samples', 'su'],
            '/api/data/samples/{parentId}/stratigraphic_units' => ['/api/data/samples/{parentId}/stratigraphic_units', 'sample'],
            '/api/data/stratigraphic_units/{parentId}/sediment_cores/depths' => ['/api/data/stratigraphic_units/{parentId}/sediment_cores/depths', 'su'],
            '/api/data/sediment_cores/{parentId}/stratigraphic_units/depths' => ['/api/data/sediment_cores/{parentId}/stratigraphic_units/depths', 'sediment_core'],
            '/api/data/stratigraphic_units/{parentId}/analyses/samples/microstratigraphy' => ['/api/data/stratigraphic_units/{parentId}/analyses/samples/microstratigraphy', 'su'],
            '/api/data/history/locations/{parentId}/animals' => ['/api/data/history/locations/{parentId}/animals', 'location'],
            '/api/data/history/locations/{parentId}/plants' => ['/api/data/history/locations/{parentId}/plants', 'location'],
        ];
    }

    #[DataProvider('csvExportProvider')]
    public function testCsvExportIsSuccessfulAndCsv(string $template, string $resolver): void
    {
        $client = self::createClient();
        $token = $this->getUserToken($client, 'user_admin');

        $path = $this->resolveCsvPath($template, $resolver, $token);

        $response = $this->apiRequest($client, 'GET', $path, [
            'token' => $token,
            'headers' => ['Accept' => 'text/csv'],
        ]);

        $this->assertSame(200, $response->getStatusCode(), sprintf('Failed for path: %s', $path));

        $headers = $response->getHeaders(false);
        $this->assertArrayHasKey('content-type', array_change_key_case($headers, CASE_LOWER));
        $contentType = $headers['content-type'][0] ?? ($headers['Content-Type'][0] ?? null);
        $this->assertNotNull($contentType, 'Missing Content-Type header');
        $this->assertStringStartsWith('text/csv', strtolower($contentType));
    }

    private function resolveCsvPath(string $template, string $resolver, string $token): string
    {
        if ('none' === $resolver) {
            return $template;
        }

        $id = null;
        switch ($resolver) {
            case 'site':
                $items = $this->getSites($token);
                $id = $items[0]['id'] ?? $this->extractId($items[0]);
                break;
            case 'su':
                $items = $this->getStratigraphicUnits(); // already authenticates internally
                $id = $items[0]['id'] ?? $this->extractId($items[0]);
                break;
            case 'sample':
                $items = $this->getSamples();
                $id = $items[0]['id'] ?? $this->extractId($items[0]);
                break;
            case 'sediment_core':
                $items = $this->getSedimentCores();
                $id = $items[0]['id'] ?? $this->extractId($items[0]);
                break;
            case 'context':
                $items = $this->getContexts();
                $id = $items[0]['id'] ?? $this->extractId($items[0]);
                break;
            case 'location':
                $items = $this->getVocabulary(['history', 'locations']);
                $id = $this->extractId($items[0]);
                break;
            default:
                throw new \InvalidArgumentException("Unknown resolver type: {$resolver}");
        }

        if (null === $id) {
            self::fail(sprintf('Could not resolve parentId for template %s using resolver %s', $template, $resolver));
        }

        return str_replace('{parentId}', (string) $id, $template);
    }

    private function extractId(array $item): ?int
    {
        if (isset($item['id']) && is_numeric($item['id'])) {
            return (int) $item['id'];
        }
        if (isset($item['@id']) && is_string($item['@id'])) {
            $parts = explode('/', $item['@id']);
            $last = end($parts);
            if (is_numeric($last)) {
                return (int) $last;
            }
        }

        return null;
    }
}
