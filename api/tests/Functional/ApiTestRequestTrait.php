<?php

namespace App\Tests\Functional;

use ApiPlatform\Symfony\Bundle\Test\Client;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

trait ApiTestRequestTrait
{
    private ?ParameterBagInterface $parameterBag = null;

    /**
     * Make an API request with optional authentication.
     *
     * @param string $method  HTTP method (GET, POST, PUT, DELETE, etc.)
     * @param string $url     The API endpoint URL
     * @param array  $options Request options (json, headers, token, etc.)
     */
    protected function apiRequest(Client $client, string $method, string $url, array $options = []): ResponseInterface
    {
        // Handle authentication token
        if (isset($options['token'])) {
            $authHeader = ['Authorization' => "Bearer {$options['token']}"];

            if (isset($options['headers'])) {
                $options['headers'] = array_merge($authHeader, $options['headers']);
            } else {
                $options['headers'] = $authHeader;
            }

            // Remove token from options since it's now in headers
            unset($options['token']);
        }

        // Handle Content-Type
        if (
            in_array(strtoupper($method), ['POST', 'PUT'])
            && !isset($options['headers']['Content-Type'])
        ) {
            $options['headers']['Content-Type'] = 'application/ld+json';
        }
        if (
            in_array(strtoupper($method), ['PATCH'])
            && !isset($options['headers']['Content-Type'])
        ) {
            $options['headers']['Content-Type'] = 'application/merge-patch+json';
        }

        return $client->request($method, $url, $options);
    }

    protected function getUsers(): array
    {
        $client = self::createClient();

        $token = $this->getUserToken($client, 'user_admin');

        $userResponse = $this->apiRequest($client, 'GET', '/api/admin/users', [
            'token' => $token,
        ]);

        $this->assertSame(200, $userResponse->getStatusCode());

        return $userResponse->toArray()['member'];
    }

    /**
     * Return the user IRI by his UUID or email.
     */
    protected function getUserIri(string $userIdOrEmail): ?string
    {
        $isUuid = preg_match(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
            $userIdOrEmail
        );
        $isEmail = filter_var($userIdOrEmail, FILTER_VALIDATE_EMAIL);

        $users = $this->getUsers();

        foreach ($users as $user) {
            if (($isUuid && $user['id'] === $userIdOrEmail)
                || ($isEmail && $user['email'] === $userIdOrEmail)) {
                return $user['@id'];
            }
        }

        return null;
    }

    protected function getSites(?string $token = null): array
    {
        $client = self::createClient();

        $userResponse = $this->apiRequest(
            $client,
            'GET',
            '/api/data/sites',
            is_string($token)
                ? ['token' => $token]
                : []
        );

        $this->assertSame(200, $userResponse->getStatusCode());

        return $userResponse->toArray()['member'];
    }

    protected function getSiteIri(mixed $siteIdOrCode): ?string
    {
        $isId = is_numeric($siteIdOrCode);
        $isCode = is_string($siteIdOrCode);

        $sites = $this->getSites();

        foreach ($sites as $site) {
            if (($isId && $site['id'] === $siteIdOrCode)
                || ($isCode && $site['code'] === $siteIdOrCode)) {
                return $site['@id'];
            }
        }

        return null;
    }

    protected function getSiteUserPrivileges(): array
    {
        $client = self::createClient();

        $token = $this->getUserToken($client, 'user_admin');

        $userResponse = $this->apiRequest($client, 'GET', '/api/admin/site_user_privileges', [
            'token' => $token,
        ]);

        $this->assertSame(200, $userResponse->getStatusCode());

        return $userResponse->toArray()['member'];
    }

    protected function getSiteUserPrivilegeIri(mixed $siteIdOrCode, mixed $userIdOrEmail): ?string
    {
        $siteIri = $this->getSiteIri($siteIdOrCode);
        $userIri = $this->getUserIri($userIdOrEmail);

        $siteUserPrivileges = $this->getSiteUserPrivileges();

        foreach ($siteUserPrivileges as $siteUserPrivilege) {
            if (
                $siteUserPrivilege['site']['@id'] === $siteIri
                && $siteUserPrivilege['user']['@id'] === $userIri
            ) {
                return $siteUserPrivilege['@id'];
            }
        }

        return null;
    }

    protected function getSiteStratigraphicUnits(): array
    {
        $client = self::createClient();

        $token = $this->getUserToken($client, 'user_admin');

        $userResponse = $this->apiRequest($client, 'GET', '/api/data/stratigraphic_units', [
            'token' => $token,
        ]);

        $this->assertSame(200, $userResponse->getStatusCode());

        return $userResponse->toArray()['member'];
    }

    protected function getContexts(): array
    {
        $client = self::createClient();

        $userResponse = $this->apiRequest($client, 'GET', '/api/data/contexts');

        $this->assertSame(200, $userResponse->getStatusCode());

        return $userResponse->toArray()['member'];
    }

    protected function getContextStratigraphicUnits(): array
    {
        $client = self::createClient();

        $userResponse = $this->apiRequest($client, 'GET', '/api/data/context_stratigraphic_units');

        $this->assertSame(200, $userResponse->getStatusCode());

        return $userResponse->toArray()['member'];
    }

    protected function getContextSamples(): array
    {
        $client = self::createClient();

        $userResponse = $this->apiRequest($client, 'GET', '/api/data/context_samples');

        $this->assertSame(200, $userResponse->getStatusCode());

        return $userResponse->toArray()['member'];
    }

    protected function getSamples(): array
    {
        $client = self::createClient();

        $userResponse = $this->apiRequest($client, 'GET', '/api/data/samples');

        $this->assertSame(200, $userResponse->getStatusCode());

        return $userResponse->toArray()['member'];
    }

    protected function getSampleStratigraphicUnits(): array
    {
        $client = self::createClient();

        $userResponse = $this->apiRequest($client, 'GET', '/api/data/sample_stratigraphic_units');

        $this->assertSame(200, $userResponse->getStatusCode());

        return $userResponse->toArray()['member'];
    }

    protected function getVocabulary(string|array $vocabulary): array
    {
        $client = self::createClient();

        if (is_array($vocabulary)) {
            $vocabulary = implode('/', $vocabulary);
        }

        $response = $this->apiRequest($client, 'GET', "/api/vocabulary/{$vocabulary}");
        $this->assertSame(200, $response->getStatusCode());

        return $response->toArray()['member'];
    }

    protected function getUserToken(Client $client, string $username, ?string $password = null): string
    {
        $loginResponse = $this->apiRequest($client, 'POST', '/api/login', [
            'json' => [
                'email' => str_ends_with($username, '@example.com') ? $username : "$username@example.com",
                'password' => $password ?? $this->parameterBag->get("app.alice.parameters.{$username}_pw"),
            ],
        ]);

        $this->assertSame(200, $loginResponse->getStatusCode());

        return $loginResponse->toArray()['token'];
    }

    protected function createTestSite(Client $client, string $token, ?array $json = null): ResponseInterface
    {
        return $this->apiRequest($client, 'POST', '/api/data/sites', [
            'token' => $token,
            'json' => $json ?? [
                'code' => $this->generateRandomSiteCode(),
                'name' => 'Test Site '.uniqid(),
                'description' => 'Test site for privilege testing',
            ],
        ]);
    }

    protected function generateRandomSiteCode(): string
    {
        $letters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $alphanumeric = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';

        return substr(str_shuffle($letters), 0, 2).
            substr(str_shuffle($alphanumeric.$alphanumeric), 0, 4);
    }

    protected function getTotalItemsCount(Client $client, string $url): int
    {
        $responseAll = $this->apiRequest($client, 'GET', $url);

        $this->assertSame(200, $responseAll->getStatusCode());

        $count = $responseAll->toArray()['totalItems'];
        $this->assertGreaterThan(0, $count);

        return $count;
    }
}
