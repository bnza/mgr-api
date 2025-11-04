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

    protected function getResourceCollectionMember(string $path, ?string $token = null): array
    {
        $client = self::createClient();

        $userResponse = $this->apiRequest(
            $client,
            'GET',
            $path,
            is_string($token)
                ? ['token' => $token]
                : []
        );

        $this->assertSame(200, $userResponse->getStatusCode());

        return $userResponse->toArray()['member'];
    }

    protected function getUsers(): array
    {
        $client = self::createClient();
        $token = $this->getUserToken($client, 'user_admin');

        return $this->getResourceCollectionMember('/api/admin/users', $token);
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

    protected function getSedimentCores(?string $token = null): array
    {
        return $this->getResourceCollectionMember('/api/data/sediment_cores', $token);
    }

    protected function getSedimentCoreDepths(?string $token = null): array
    {
        return $this->getResourceCollectionMember('/api/data/sediment_core_depths', $token);
    }

    protected function getAnalysisMicrostratigraphicUnits(?string $token = null): array
    {
        return $this->getResourceCollectionMember('/api/data/analyses/samples/microstratigraphy', $token);
    }

    protected function getAnalysisAnthropology(?string $token = null): array
    {
        return $this->getResourceCollectionMember('/api/data/analyses/sites/anthropology', $token);
    }

    protected function getMicrostratigraphicUnits(?string $token = null): array
    {
        return $this->getResourceCollectionMember('/api/data/microstratigraphic_units', $token);
    }

    protected function getMediaObject(?string $token = null): array
    {
        return $this->getResourceCollectionMember('/api/data/media_objects', $token);
    }

    protected function getSites(?string $token = null): array
    {
        return $this->getResourceCollectionMember('/api/data/sites', $token);
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

        return $this->getResourceCollectionMember('/api/admin/site_user_privileges', $token);
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

    protected function getAnalysisContextBotany(): array
    {
        return $this->getResourceCollectionMember('/api/data/analyses/contexts/botany');
    }

    protected function getAnalysisContextZoos(): array
    {
        return $this->getResourceCollectionMember('/api/data/analyses/contexts/zoo');
    }

    protected function getAnalysisIndividuals(): array
    {
        return $this->getResourceCollectionMember('/api/data/analyses/individuals');
    }

    protected function getAnalysisBotanySeeds(): array
    {
        return $this->getResourceCollectionMember('/api/data/analyses/botany/seeds');
    }

    protected function getAnalysisBotanyCharcoals(): array
    {
        return $this->getResourceCollectionMember('/api/data/analyses/botany/charcoals');
    }

    protected function getStratigraphicUnits(): array
    {
        $client = self::createClient();
        $token = $this->getUserToken($client, 'user_admin');

        return $this->getResourceCollectionMember('/api/data/stratigraphic_units', $token);
    }

    protected function getContexts(): array
    {
        return $this->getResourceCollectionMember('/api/data/contexts');
    }

    protected function getContextStratigraphicUnits(): array
    {
        return $this->getResourceCollectionMember('/api/data/context_stratigraphic_units');
    }

    protected function getBotanySeeds(): array
    {
        return $this->getResourceCollectionMember('/api/data/botany/seeds');
    }

    protected function getBotanyCharcoals(): array
    {
        return $this->getResourceCollectionMember('/api/data/botany/charcoals');
    }

    protected function getContextSamples(): array
    {
        return $this->getResourceCollectionMember('/api/data/context_samples');
    }

    protected function getIndividuals(): array
    {
        $client = self::createClient();
        $token = $this->getUserToken($client, 'user_admin');

        return $this->getResourceCollectionMember('/api/data/individuals', $token);
    }

    protected function getPotteries(): array
    {
        $client = self::createClient();
        $token = $this->getUserToken($client, 'user_admin');

        return $this->getResourceCollectionMember('/api/data/potteries', $token);
    }

    protected function getPotteryAnalyses(): array
    {
        return $this->getResourceCollectionMember('/api/data/analyses/potteries');
    }

    protected function getSamples(): array
    {
        return $this->getResourceCollectionMember('/api/data/samples');
    }

    protected function getSampleStratigraphicUnits(): array
    {
        return $this->getResourceCollectionMember('/api/data/sample_stratigraphic_units');
    }

    private function getMediaObjectStratigraphicUnits(): array
    {
        return $this->getResourceCollectionMember('/api/data/media_object_stratigraphic_units');
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
