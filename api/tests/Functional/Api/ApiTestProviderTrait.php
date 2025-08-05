<?php

namespace App\Tests\Functional\Api;

use ApiPlatform\Symfony\Bundle\Test\Client;

trait ApiTestProviderTrait
{
    public static function nonAdminUserProvider(): array
    {
        return [
            'user_base' => ['user_base'],
            'user_editor' => ['user_editor'],
            'user_geo' => ['user_geo'],
        ];
    }

    public static function editorUserProvider(): array
    {
        return [
            'user_admin' => ['user_admin'],
            'user_editor' => ['user_editor'],
        ];
    }

    public static function nonEditorUserProvider(): array
    {
        return [
            'user_base' => ['user_base'],
            'user_geo' => ['user_geo'],
        ];
    }

    /**
     * Get fixture sites without creating new ones.
     */
    protected function getFixtureSites(array $queryParams = []): array
    {
        $client = self::createClient();
        $url = '/api/data/sites';
        if (!empty($queryParams)) {
            $url .= '?'.http_build_query($queryParams);
        }
        $response = $this->apiRequest($client, 'GET', $url);
        $this->assertSame(200, $response->getStatusCode());

        return $response->toArray()['member'];
    }

    /**
     * Get fixture contexts without creating new ones.
     */
    protected function getFixtureContexts(array $queryParams = []): array
    {
        $client = self::createClient();
        $url = '/api/data/contexts';
        if (!empty($queryParams)) {
            $url .= '?'.http_build_query($queryParams);
        }
        $response = $this->apiRequest($client, 'GET', $url);
        $this->assertSame(200, $response->getStatusCode());

        return $response->toArray()['member'];
    }

    /**
     * Get fixture stratigraphic units without creating new ones.
     */
    protected function getFixtureStratigraphicUnits(array $queryParams = []): array
    {
        $client = self::createClient();
        $url = '/api/data/stratigraphic_units';
        if (!empty($queryParams)) {
            $url .= '?'.http_build_query($queryParams);
        }
        $response = $this->apiRequest($client, 'GET', $url);
        $this->assertSame(200, $response->getStatusCode());

        return $response->toArray()['member'];
    }

    /**
     * Get fixture stratigraphic units without creating new ones.
     */
    protected function getFixtureContextStratigraphicUnits(array $queryParams = []): array
    {
        $client = self::createClient();
        $url = '/api/data/context_stratigraphic_units';
        if (!empty($queryParams)) {
            $url .= '?'.http_build_query($queryParams);
        }
        $response = $this->apiRequest($client, 'GET', $url);
        $this->assertSame(200, $response->getStatusCode());

        return $response->toArray()['member'];
    }

    /**
     * Get a specific fixture site by code.
     */
    protected function getFixtureSiteByCode(string $code): ?array
    {
        // Filter by code to get specific site
        $sites = $this->getFixtureSites(['code' => $code]);

        foreach ($sites as $site) {
            if ($site['code'] === $code) {
                return $site;
            }
        }

        return null;
    }

    /**
     * Get a specific fixture context by name.
     */
    protected function getFixtureContextByName(string $name): ?array
    {
        // Use a larger page size to get more results
        $contexts = $this->getFixtureContexts(['itemsPerPage' => 100]);

        foreach ($contexts as $context) {
            if ($context['name'] === $name) {
                return $context;
            }
        }

        return null;
    }

    /**
     * Get a specific fixture stratigraphic unit by site and number.
     */
    protected function getFixtureStratigraphicUnit(string $siteCode, int $number): ?array
    {
        $site = $this->getFixtureSiteByCode($siteCode);

        if (!$site) {
            return null;
        }

        // Use API filters to find stratigraphic units for the specific site and number
        // Based on the StratigraphicUnit entity, we can filter by site.code and number
        $queryParams = [
            'site' => $site['@id'],
            'number' => $number,
            'itemsPerPage' => 10, // Should be enough to find the specific item
        ];

        $sus = $this->getFixtureStratigraphicUnits($queryParams);

        foreach ($sus as $su) {
            // Check if the SU matches the number
            if ($su['number'] === $number) {
                // Try to match by site IRI/ID
                if (isset($su['site']['@id']) && $su['site']['@id'] === $site['@id']) {
                    return $su;
                }
                // Fallback: try to match by site code if available
                if (isset($su['site']['code']) && $su['site']['code'] === $siteCode) {
                    return $su;
                }
                // If site is just an IRI string, compare directly
                if (is_string($su['site']) && $su['site'] === $site['@id']) {
                    return $su;
                }
            }
        }

        return null;
    }

    /**
     * Create stratigraphic unit only when needed for testing.
     */
    protected function createStratigraphicUnit(Client $client, string $username): array
    {
        $token = $this->getUserToken($client, $username);
        $site = $this->getFixtureSiteByCode('ME'); // Use fixture site

        // Generate unique number to avoid conflicts
        $uniqueNumber = random_int(9000, 9999);

        $payload = [
            'site' => $site['@id'],
            'year' => 2025,
            'number' => $uniqueNumber,
            'description' => 'Test stratigraphic unit',
            'interpretation' => 'Test interpretation',
        ];

        $response = $this->apiRequest($client, 'POST', '/api/data/stratigraphic_units', [
            'token' => $token,
            'json' => $payload,
        ]);

        $this->assertSame(201, $response->getStatusCode());

        return $response->toArray();
    }
}
